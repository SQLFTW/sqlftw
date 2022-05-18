<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dal\User\AlterCurrentUserCommand;
use SqlFtw\Sql\Dal\User\AlterUserCommand;
use SqlFtw\Sql\Dal\User\AlterUserDefaultRoleCommand;
use SqlFtw\Sql\Dal\User\CreateRoleCommand;
use SqlFtw\Sql\Dal\User\CreateUserCommand;
use SqlFtw\Sql\Dal\User\DropRoleCommand;
use SqlFtw\Sql\Dal\User\DropUserCommand;
use SqlFtw\Sql\Dal\User\GrantCommand;
use SqlFtw\Sql\Dal\User\GrantProxyCommand;
use SqlFtw\Sql\Dal\User\GrantRoleCommand;
use SqlFtw\Sql\Dal\User\IdentifiedUser;
use SqlFtw\Sql\Dal\User\IdentifiedUserAction;
use SqlFtw\Sql\Dal\User\RenameUserCommand;
use SqlFtw\Sql\Dal\User\RevokeAllCommand;
use SqlFtw\Sql\Dal\User\RevokeCommand;
use SqlFtw\Sql\Dal\User\RevokeProxyCommand;
use SqlFtw\Sql\Dal\User\RevokeRoleCommand;
use SqlFtw\Sql\Dal\User\RolesSpecification;
use SqlFtw\Sql\Dal\User\RolesSpecificationType;
use SqlFtw\Sql\Dal\User\SetDefaultRoleCommand;
use SqlFtw\Sql\Dal\User\SetPasswordCommand;
use SqlFtw\Sql\Dal\User\SetRoleCommand;
use SqlFtw\Sql\Dal\User\UserDefaultRolesSpecification;
use SqlFtw\Sql\Dal\User\UserPasswordLockOption;
use SqlFtw\Sql\Dal\User\UserPasswordLockOptionType;
use SqlFtw\Sql\Dal\User\UserPrivilege;
use SqlFtw\Sql\Dal\User\UserPrivilegeResource;
use SqlFtw\Sql\Dal\User\UserPrivilegeResourceType;
use SqlFtw\Sql\Dal\User\UserPrivilegeType;
use SqlFtw\Sql\Dal\User\UserResourceOption;
use SqlFtw\Sql\Dal\User\UserResourceOptionType;
use SqlFtw\Sql\Dal\User\UserTlsOption;
use SqlFtw\Sql\Dal\User\UserTlsOptionType;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\UserName;
use function array_keys;
use function strtoupper;

class UserCommandsParser
{
    use StrictBehaviorMixin;

    /**
     * ALTER USER [IF EXISTS]
     *     user [auth_option] [, user [auth_option]] ...
     *     [REQUIRE {NONE | tls_option [[AND] tls_option] ...}]
     *     [WITH resource_option [resource_option] ...]
     *     [password_option | lock_option] ...
     *     [COMMENT 'comment_string' | ATTRIBUTE 'json_object']
     *
     * ALTER USER [IF EXISTS]
     *     USER() IDENTIFIED BY 'auth_string'
     *
     * ALTER USER [IF EXISTS]
     *     user DEFAULT ROLE
     *     {NONE | ALL | role [, role ] ...}
     */
    public function parseAlterUser(TokenList $tokenList): Command
    {
        $tokenList->expectKeywords(Keyword::ALTER, Keyword::USER);
        $ifExists = $tokenList->hasKeywords(Keyword::IF, Keyword::EXISTS);

        if ($tokenList->hasKeyword(Keyword::USER)) {
            $tokenList->expectSymbol('(');
            $tokenList->expectSymbol(')');
            $tokenList->expectKeywords(Keyword::IDENTIFIED, Keyword::BY);
            $password = $tokenList->expectString();

            return new AlterCurrentUserCommand($password, $ifExists);
        } elseif ($tokenList->seekKeyword(Keyword::DEFAULT, 4)) {
            $user = $tokenList->expectUserName();
            $tokenList->expectKeywords(Keyword::DEFAULT, Keyword::ROLE);
            $role = $this->parseRoleSpecification($tokenList);

            return new AlterUserDefaultRoleCommand($user, $role, $ifExists);
        }

        $users = $this->parseIdentifiedUsers($tokenList);
        $tlsOptions = $this->parseTlsOptions($tokenList);
        $resourceOptions = $this->parseResourceOptions($tokenList);
        $passwordLockOptions = $this->parsePasswordLockOptions($tokenList);

        $comment = $attribute = null;
        if ($tokenList->hasKeyword(Keyword::COMMENT)) {
            $comment = $tokenList->expectString();
        } elseif ($tokenList->hasKeyword(Keyword::ATTRIBUTE)) {
            $attribute = $tokenList->expectString();
        }

        return new AlterUserCommand($users, $tlsOptions, $resourceOptions, $passwordLockOptions, $comment, $attribute, $ifExists);
    }

    /**
     * CREATE USER [IF NOT EXISTS]
     *     user [auth_option] [, user [auth_option]] ...
     *     [DEFAULT ROLE role [, role ] ...]
     *     [REQUIRE {NONE | tls_option [[AND] tls_option] ...}]
     *     [WITH resource_option [resource_option] ...]
     *     [password_option | lock_option] ...
     *     [COMMENT 'comment_string' | ATTRIBUTE 'json_object']
     */
    public function parseCreateUser(TokenList $tokenList): CreateUserCommand
    {
        $tokenList->expectKeywords(Keyword::CREATE, Keyword::USER);
        $ifNotExists = $tokenList->hasKeywords(Keyword::IF, Keyword::NOT, Keyword::EXISTS);

        $users = $this->parseIdentifiedUsers($tokenList);

        $defaultRoles = null;
        if ($tokenList->hasKeywords(Keyword::DEFAULT, Keyword::ROLE)) {
            $defaultRoles = $this->parseRolesList($tokenList);
        }

        $tlsOptions = $this->parseTlsOptions($tokenList);
        $resourceOptions = $this->parseResourceOptions($tokenList);
        $passwordLockOptions = $this->parsePasswordLockOptions($tokenList);

        $comment = $attribute = null;
        if ($tokenList->hasKeyword(Keyword::COMMENT)) {
            $comment = $tokenList->expectString();
        } elseif ($tokenList->hasKeyword(Keyword::ATTRIBUTE)) {
            $attribute = $tokenList->expectString();
        }

        return new CreateUserCommand($users, $defaultRoles, $tlsOptions, $resourceOptions, $passwordLockOptions, $comment, $attribute, $ifNotExists);
    }

    /**
     * user [auth_option] [, user [auth_option]] ...
     *
     * auth_option: {
     *     IDENTIFIED BY 'auth_string'
     *         [REPLACE 'current_auth_string']
     *         [RETAIN CURRENT PASSWORD]
     *   | IDENTIFIED WITH auth_plugin
     *   | IDENTIFIED WITH auth_plugin BY 'auth_string'
     *         [REPLACE 'current_auth_string']
     *         [RETAIN CURRENT PASSWORD]
     *   | IDENTIFIED WITH auth_plugin AS 'hash_string'
     *   | DISCARD OLD PASSWORD
     * }
     *
     * @return non-empty-array<IdentifiedUser>
     */
    private function parseIdentifiedUsers(TokenList $tokenList): array
    {
        $users = [];
        do {
            $user = $tokenList->expectUserName();
            if ($tokenList->hasKeywords(Keyword::DISCARD, Keyword::OLD, Keyword::PASSWORD)) {
                $action = IdentifiedUserAction::DISCARD_OLD_PASSWORD;
                $users[] = new IdentifiedUser($user, IdentifiedUserAction::get($action));
                continue;
            }

            if (!$tokenList->hasKeyword(Keyword::IDENTIFIED)) {
                $users[] = new IdentifiedUser($user);
                continue;
            }

            $action = $plugin = $password = $replace = null;
            $retainCurrent = false;
            if ($tokenList->hasKeyword(Keyword::WITH)) {
                $action = IdentifiedUserAction::SET_PLUGIN;
                $plugin = $tokenList->expectNameOrString();
                if ($tokenList->hasKeyword(Keyword::AS)) {
                    $action = IdentifiedUserAction::SET_HASH;
                    $password = $tokenList->expectStringLike();
                }
            }
            if ($action !== IdentifiedUserAction::SET_HASH && $tokenList->hasKeyword(Keyword::BY)) {
                $action = IdentifiedUserAction::SET_PASSWORD;
                $password = $tokenList->expectStringLike();
                if ($tokenList->hasKeyword(Keyword::REPLACE)) {
                    $replace = $tokenList->expectStringLike();
                }
                if ($tokenList->hasKeywords(Keyword::RETAIN, Keyword::CURRENT, Keyword::PASSWORD)) {
                    $retainCurrent = true;
                }
            }

            if ($action !== null) {
                $action = IdentifiedUserAction::get($action);
            }

            $users[] = new IdentifiedUser($user, $action, $password, $plugin, $replace, $retainCurrent);
        } while ($tokenList->hasSymbol(','));

        return $users;
    }

    /**
     * [REQUIRE {NONE | tls_option [[AND] tls_option] ...}]
     *
     * tls_option: {
     *     SSL
     *   | X509
     *   | CIPHER 'cipher'
     *   | ISSUER 'issuer'
     *   | SUBJECT 'subject'
     * }
     *
     * @return UserTlsOption[]|null
     */
    private function parseTlsOptions(TokenList $tokenList): ?array
    {
        $tlsOptions = null;
        if ($tokenList->hasKeyword(Keyword::REQUIRE)) {
            if ($tokenList->hasKeyword(Keyword::NONE)) {
                $tlsOptions = [];
            } else {
                $tlsOptions = [];
                do {
                    /** @var UserTlsOptionType $type */
                    $type = $tokenList->expectKeywordEnum(UserTlsOptionType::class);
                    $value = $tokenList->getString();
                    $tlsOptions[] = new UserTlsOption($type, $value);

                    if (!$tokenList->hasKeyword(Keyword::AND)) {
                        break;
                    }
                } while (true);
            }
        }

        return $tlsOptions;
    }

    /**
     * [WITH resource_option [resource_option] ...]
     *
     * resource_option: {
     *     MAX_QUERIES_PER_HOUR count
     *   | MAX_UPDATES_PER_HOUR count
     *   | MAX_CONNECTIONS_PER_HOUR count
     *   | MAX_USER_CONNECTIONS count
     * }
     *
     * @return non-empty-array<UserResourceOption>|null
     */
    private function parseResourceOptions(TokenList $tokenList): ?array
    {
        if (!$tokenList->hasKeyword(Keyword::WITH)) {
            return null;
        }

        $resourceOptions = [];
        $type = $tokenList->expectKeywordEnum(UserResourceOptionType::class);
        do {
            $value = $tokenList->expectUnsignedInt();
            $resourceOptions[] = new UserResourceOption($type, $value);
            $type = $tokenList->getKeywordEnum(UserResourceOptionType::class);
        } while ($type !== null);

        return $resourceOptions;
    }

    /**
     * password_option: {
     *     PASSWORD EXPIRE [DEFAULT | NEVER | INTERVAL N DAY]
     *   | PASSWORD HISTORY {DEFAULT | N}
     *   | PASSWORD REUSE INTERVAL {DEFAULT | N DAY}
     *   | PASSWORD REQUIRE CURRENT [DEFAULT | OPTIONAL]
     * }
     *
     * lock_option: {
     *     ACCOUNT LOCK
     *   | ACCOUNT UNLOCK
     * }
     *
     * @return non-empty-array<UserPasswordLockOption>|null
     */
    private function parsePasswordLockOptions(TokenList $tokenList): ?array
    {
        $passwordLockOptions = [];
        while ($keyword = $tokenList->getAnyKeyword(Keyword::PASSWORD, Keyword::ACCOUNT)) {
            if ($keyword === Keyword::ACCOUNT) {
                $keyword = $tokenList->expectAnyKeyword(Keyword::LOCK, Keyword::UNLOCK);
                $passwordLockOptions[] = new UserPasswordLockOption(UserPasswordLockOptionType::get(UserPasswordLockOptionType::ACCOUNT), $keyword);
                continue;
            }

            $keyword = $tokenList->expectAnyKeyword(Keyword::EXPIRE, Keyword::HISTORY, Keyword::REUSE, Keyword::REQUIRE);
            if ($keyword === Keyword::EXPIRE) {
                $value = $tokenList->getAnyKeyword(Keyword::DEFAULT, Keyword::NEVER, Keyword::INTERVAL);
                if ($value === Keyword::INTERVAL) {
                    $value = $tokenList->expectInt();
                    $tokenList->expectKeyword(Keyword::DAY);
                }
                $passwordLockOptions[] = new UserPasswordLockOption(UserPasswordLockOptionType::get(UserPasswordLockOptionType::PASSWORD_EXPIRE), $value);
            } elseif ($keyword === Keyword::HISTORY) {
                $value = Keyword::DEFAULT;
                if (!$tokenList->hasKeyword(Keyword::DEFAULT)) {
                    $value = $tokenList->expectInt();
                }
                $passwordLockOptions[] = new UserPasswordLockOption(UserPasswordLockOptionType::get(UserPasswordLockOptionType::PASSWORD_HISTORY), $value);
            } elseif ($keyword === Keyword::REUSE) {
                $tokenList->expectKeyword(Keyword::INTERVAL);
                $value = Keyword::DEFAULT;
                if (!$tokenList->hasKeyword(Keyword::DEFAULT)) {
                    $value = $tokenList->expectInt();
                    $tokenList->expectKeyword(Keyword::DAY);
                }
                $passwordLockOptions[] = new UserPasswordLockOption(UserPasswordLockOptionType::get(UserPasswordLockOptionType::PASSWORD_REUSE_INTERVAL), $value);
            } else {
                $tokenList->expectKeyword(Keyword::CURRENT);
                $value = $tokenList->getAnyKeyword(Keyword::DEFAULT, Keyword::OPTIONAL);
                $passwordLockOptions[] = new UserPasswordLockOption(UserPasswordLockOptionType::get(UserPasswordLockOptionType::PASSWORD_REQUIRE_CURRENT), $value);
            }
        }

        return $passwordLockOptions !== [] ? $passwordLockOptions : null;
    }

    /**
     * CREATE ROLE [IF NOT EXISTS] role [, role ] ...
     */
    public function parseCreateRole(TokenList $tokenList): CreateRoleCommand
    {
        $tokenList->expectKeywords(Keyword::CREATE, Keyword::ROLE);
        $ifNotExists = $tokenList->hasKeywords(Keyword::IF, Keyword::NOT, Keyword::EXISTS);
        $roles = $this->parseRolesList($tokenList);

        return new CreateRoleCommand($roles, $ifNotExists);
    }

    /**
     * DROP ROLE [IF EXISTS] role [, role ] ...
     */
    public function parseDropRole(TokenList $tokenList): DropRoleCommand
    {
        $tokenList->expectKeywords(Keyword::DROP, Keyword::ROLE);
        $ifExists = $tokenList->hasKeywords(Keyword::IF, Keyword::EXISTS);
        $roles = $this->parseRolesList($tokenList);

        return new DropRoleCommand($roles, $ifExists);
    }

    /**
     * DROP USER [IF EXISTS] user [, user] ...
     */
    public function parseDropUser(TokenList $tokenList): DropUserCommand
    {
        $tokenList->expectKeywords(Keyword::DROP, Keyword::USER);
        $ifExists = $tokenList->hasKeywords(Keyword::IF, Keyword::EXISTS);
        $users = $this->parseUserList($tokenList);

        return new DropUserCommand($users, $ifExists);
    }

    /**
     * Crom! Grant me revenge! And if you do not listen, then to hell with you!
     *
     * GRANT
     *     priv_type [(column_list)]
     *       [, priv_type [(column_list)]] ...
     *     ON [object_type] priv_level
     *     TO user_or_role [, user_or_role] ...
     *     [WITH GRANT OPTION]
     *     [AS user
     *       [WITH ROLE {DEFAULT | NONE | ALL | ALL EXCEPT role [, role ] ... | role [, role ] ...}]
     *     ]
     *
     * GRANT PROXY ON user
     *     TO user [, user] ...
     *     [WITH GRANT OPTION]
     *
     * GRANT role [, role] ...
     *     TO user [, user] ...
     *     [WITH ADMIN OPTION]
     *
     * MySQL 5.x:
     * GRANT
     *     priv_type [(column_list)]
     *       [, priv_type [(column_list)]] ...
     *     ON [object_type] priv_level
     *     TO user [auth_option] [, user [auth_option]] ...
     *     [REQUIRE {NONE | tls_option [[AND] tls_option] ...}]
     *     [WITH {GRANT OPTION | resource_option} ...]
     */
    public function parseGrant(TokenList $tokenList): Command
    {
        $tokenList->expectKeyword(Keyword::GRANT);

        if ($tokenList->hasKeywords(Keyword::PROXY, Keyword::ON)) {
            $proxy = $tokenList->expectUserName();
            $tokenList->expectKeyword(Keyword::TO);
            $users = $this->parseUserList($tokenList);
            $withGrantOption = $tokenList->hasKeywords(Keyword::WITH, Keyword::GRANT, Keyword::OPTION);

            return new GrantProxyCommand($proxy, $users, $withGrantOption);
        } elseif (!$tokenList->seekKeyword(Keyword::ON, 1000)) {
            $roles = $this->parseRolesList($tokenList);
            $tokenList->expectKeyword(Keyword::TO);
            $users = $this->parseUserList($tokenList);
            $withAdminOption = $tokenList->hasKeywords(Keyword::WITH, Keyword::ADMIN, Keyword::OPTION);

            return new GrantRoleCommand($roles, $users, $withAdminOption);
        } else {
            $privileges = $this->parsePrivilegesList($tokenList);
            $resource = $this->parseResource($tokenList);
            $tokenList->expectKeyword(Keyword::TO);
            $users = $this->parseIdentifiedUsers($tokenList);
            // 5.x only
            $tlsOptions = $this->parseTlsOptions($tokenList);
            $withGrantOption = $tokenList->hasKeywords(Keyword::WITH, Keyword::GRANT, Keyword::OPTION);
            // 5.x only
            $resourceOptions = $this->parseResourceOptions($tokenList);
            $as = $role = null;
            if ($tokenList->hasKeyword(Keyword::AS)) {
                $as = $tokenList->expectUserName();
                if ($tokenList->hasKeywords(Keyword::WITH, Keyword::ROLE)) {
                    $role = $this->parseRoleSpecification($tokenList);
                }
            }

            return new GrantCommand($privileges, $resource, $users, $as, $role, $tlsOptions, $resourceOptions, $withGrantOption);
        }
    }

    /**
     * priv_type [(column_list)] [, priv_type [(column_list)]] ...
     *
     * @return non-empty-array<UserPrivilege>
     */
    private function parsePrivilegesList(TokenList $tokenList): array
    {
        $privileges = [];
        do {
            $type = $tokenList->getNonKeywordName();
            if ($type !== null) {
                // dynamic (names)
                if (!UserPrivilegeType::isValid(strtoupper($type))) {
                    $tokenList->expectKeywordEnum(UserPrivilegeType::class);
                }
            } else {
                // static (keywords)
                $types = UserPrivilegeType::getFistAndSecondKeywords();
                $type = $tokenList->expectAnyKeyword(...array_keys($types));
                if ($type === Keyword::ALL) {
                    $tokenList->passKeyword(Keyword::PRIVILEGES);
                    $next = null;
                } elseif ($type === Keyword::CREATE) {
                    /** @var string[] $next */
                    $next = $types[$type];
                    $next = $tokenList->getAnyKeyword(...$next);
                    if ($next === Keyword::TEMPORARY) {
                        $tokenList->expectKeyword(Keyword::TABLES);
                        $next .= ' ' . Keyword::TABLES;
                    }
                } elseif ($type === Keyword::ALTER) {
                    /** @var string[] $next */
                    $next = $types[$type];
                    $next = $tokenList->getAnyKeyword(...$next);
                } else {
                    $next = $types[$type];
                    if ($next !== null) {
                        $next = $tokenList->expectAnyKeyword(...$next);
                    }
                }
                if ($next !== null) {
                    $type .= ' ' . $next;
                }
            }

            $columns = null;
            if ($tokenList->hasSymbol('(')) {
                $columns = [];
                do {
                    $columns[] = $tokenList->expectName();
                } while ($tokenList->hasSymbol(','));
                $tokenList->expectSymbol(')');
            }
            $privileges[] = new UserPrivilege(UserPrivilegeType::get(strtoupper($type)), $columns);
        } while ($tokenList->hasSymbol(','));

        return $privileges;
    }

    /**
     * ON [object_type] priv_level
     *
     * object_type: {
     *     TABLE
     *   | FUNCTION
     *   | PROCEDURE
     * }
     *
     * priv_level: {
     *     *
     *   | *.*
     *   | db_name.*
     *   | db_name.tbl_name
     *   | tbl_name
     *   | db_name.routine_name
     * }
     */
    private function parseResource(TokenList $tokenList): UserPrivilegeResource
    {
        $tokenList->expectKeyword(Keyword::ON);
        /** @var UserPrivilegeResourceType|null $resourceType */
        $resourceType = $tokenList->getKeywordEnum(UserPrivilegeResourceType::class);
        if ($tokenList->hasOperator(Operator::MULTIPLY)) {
            $object = false;
            if ($tokenList->hasSymbol('.')) {
                $tokenList->expectOperator(Operator::MULTIPLY);
                $object = true;
            }

            return new UserPrivilegeResource(UserPrivilegeResource::ALL, $object ? UserPrivilegeResource::ALL : null, $resourceType);
        } else {
            $name = $tokenList->expectName();
            if ($tokenList->hasSymbol('.')) {
                $schema = $name;
                if ($tokenList->hasOperator(Operator::MULTIPLY)) {
                    return new UserPrivilegeResource($schema, UserPrivilegeResource::ALL, $resourceType);
                } else {
                    $name = $tokenList->expectName();

                    return new UserPrivilegeResource($schema, $name, $resourceType);
                }
            } else {
                return new UserPrivilegeResource(null, $name, $resourceType);
            }
        }
    }

    /**
     * RENAME USER old_user TO new_user
     *     [, old_user TO new_user] ...
     */
    public function parseRenameUser(TokenList $tokenList): RenameUserCommand
    {
        $tokenList->expectKeywords(Keyword::RENAME, Keyword::USER);

        $users = [];
        $newUsers = [];
        do {
            $users[] = $tokenList->expectUserName();
            $tokenList->expectKeyword(Keyword::TO);
            $newUsers[] = $tokenList->expectUserName();
        } while ($tokenList->hasSymbol(','));

        return new RenameUserCommand($users, $newUsers);
    }

    /**
     * REVOKE
     *     priv_type [(column_list)]
     *       [, priv_type [(column_list)]] ...
     *     ON [object_type] priv_level
     *     FROM user [, user] ...
     *
     * REVOKE ALL [PRIVILEGES], GRANT OPTION
     *     FROM user [, user] ...
     *
     * REVOKE PROXY ON user
     *     FROM user [, user] ...
     *
     * REVOKE role [, role ] ...
     *     FROM user [, user ] ...
     */
    public function parseRevoke(TokenList $tokenList): Command
    {
        $tokenList->expectKeyword(Keyword::REVOKE);

        if ($tokenList->hasKeyword(Keyword::ALL)) {
            if (!$tokenList->seekKeyword(Keyword::ON, 15)) {
                $tokenList->passKeyword(Keyword::PRIVILEGES);
                $tokenList->expectSymbol(',');
                $tokenList->expectKeywords(Keyword::GRANT, Keyword::OPTION, Keyword::FROM);
                $users = $this->parseUserList($tokenList);

                return new RevokeAllCommand($users);
            } else {
                $tokenList->resetPosition(-1);
            }
        }
        if ($tokenList->hasKeywords(Keyword::PROXY)) {
            $tokenList->expectKeyword(Keyword::ON);
            $proxy = $tokenList->expectUserName();
            $tokenList->expectKeyword(Keyword::FROM);
            $users = $this->parseUserList($tokenList);

            return new RevokeProxyCommand($proxy, $users);
        } elseif ($tokenList->seekKeyword(Keyword::ON, 1000)) {
            $privileges = $this->parsePrivilegesList($tokenList);
            $resource = $this->parseResource($tokenList);
            $tokenList->expectKeyword(Keyword::FROM);
            $users = $this->parseUserList($tokenList);

            return new RevokeCommand($privileges, $resource, $users);
        } else {
            $roles = $this->parseRolesList($tokenList);
            $tokenList->expectKeyword(Keyword::FROM);
            $users = $this->parseUserList($tokenList);

            return new RevokeRoleCommand($roles, $users);
        }
    }

    /**
     * SET DEFAULT ROLE
     *     {NONE | ALL | role [, role ] ...}
     *     TO user [, user ] ...
     */
    public function parseSetDefaultRole(TokenList $tokenList): SetDefaultRoleCommand
    {
        $tokenList->expectKeywords(Keyword::SET, Keyword::DEFAULT, Keyword::ROLE);
        /** @var UserDefaultRolesSpecification|null $roles */
        $roles = $tokenList->getKeywordEnum(UserDefaultRolesSpecification::class);
        $rolesList = null;
        if ($roles === null) {
            $rolesList = $this->parseRolesList($tokenList);
        }

        $tokenList->expectKeyword(Keyword::TO);
        $users = $this->parseUserList($tokenList);

        return new SetDefaultRoleCommand($users, $roles, $rolesList);
    }

    /**
     * 8.0 https://dev.mysql.com/doc/refman/8.0/en/set-password.html
     * SET PASSWORD [FOR user] auth_option
     *   [REPLACE 'current_auth_string']
     *   [RETAIN CURRENT PASSWORD]
     *
     * auth_option: {
     *     = 'auth_string'
     *   | TO RANDOM
     * }
     *
     * 5.7 https://dev.mysql.com/doc/refman/5.7/en/set-password.html
     * SET PASSWORD [FOR user] = password_option
     *
     * password_option: {
     *     PASSWORD('auth_string')
     *   | 'auth_string'
     * }
     */
    public function parseSetPassword(TokenList $tokenList): SetPasswordCommand
    {
        $tokenList->expectKeywords(Keyword::SET, Keyword::PASSWORD);
        $user = null;
        if ($tokenList->hasKeyword(Keyword::FOR)) {
            $user = $tokenList->expectUserName();
        }

        $passwordFunction = $password = $replace = null;
        if ($tokenList->using(null, 80000)) {
            if ($tokenList->hasOperator(Operator::EQUAL)) {
                $password = $tokenList->expectString();
            } else {
                $tokenList->expectKeywords(Keyword::TO, Keyword::RANDOM);
            }
            if ($tokenList->hasKeyword(Keyword::REPLACE)) {
                $replace = $tokenList->expectString();
            }
            $retain = $tokenList->hasKeywords(Keyword::RETAIN, Keyword::CURRENT, Keyword::PASSWORD);
        } else {
            $tokenList->expectOperator(Operator::EQUAL);
            $passwordFunction = $tokenList->using(null, 50700)
                ? $tokenList->getAnyKeyword(Keyword::PASSWORD)
                : $tokenList->getAnyKeyword(Keyword::PASSWORD, Keyword::OLD_PASSWORD);
            if ($passwordFunction !== null) {
                $tokenList->expectSymbol('(');
            }
            $password = $tokenList->expectString();
            if ($passwordFunction !== null) {
                $tokenList->expectSymbol(')');
            }
            $retain = false;
        }

        return new SetPasswordCommand($user, $passwordFunction, $password, $replace, $retain);
    }

    /**
     * SET ROLE {
     *     DEFAULT
     *   | NONE
     *   | ALL
     *   | ALL EXCEPT role [, role ] ...
     *   | role [, role ] ...
     * }
     */
    public function parseSetRole(TokenList $tokenList): SetRoleCommand
    {
        $tokenList->expectKeywords(Keyword::SET, Keyword::ROLE);
        $role = $this->parseRoleSpecification($tokenList);

        return new SetRoleCommand($role);
    }

    private function parseRoleSpecification(TokenList $tokenList): RolesSpecification
    {
        $keyword = $tokenList->getAnyKeyword(Keyword::DEFAULT, Keyword::NONE, Keyword::ALL);
        $except = false;
        if ($keyword === Keyword::ALL) {
            $except = $tokenList->hasKeyword(Keyword::EXCEPT);
        }
        $type = $keyword !== null
            ? ($except ? RolesSpecificationType::ALL_EXCEPT : $keyword)
            : RolesSpecificationType::LIST;

        $roles = null;
        if ($except !== false || $keyword === null) {
            $roles = $this->parseRolesList($tokenList);
        }

        return new RolesSpecification(RolesSpecificationType::get($type), $roles);
    }

    /**
     * @return non-empty-array<UserName>
     */
    private function parseUserList(TokenList $tokenList): array
    {
        $users = [];
        do {
            $users[] = $tokenList->expectUserName();
        } while ($tokenList->hasSymbol(','));

        return $users;
    }

    /**
     * @return non-empty-array<string>
     */
    private function parseRolesList(TokenList $tokenList): array
    {
        $roles = [];
        do {
            $roles[] = $tokenList->expectNameOrString();
        } while ($tokenList->hasSymbol(','));

        return $roles;
    }

}
