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
use SqlFtw\Parser\TokenType;
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

class UserCommandsParser
{
    use StrictBehaviorMixin;

    /**
     * ALTER USER [IF EXISTS]
     *     user [auth_option] [, user [auth_option]] ...
     *     [REQUIRE {NONE | tls_option [[AND] tls_option] ...}]
     *     [WITH resource_option [resource_option] ...]
     *     [password_option | lock_option] ...
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
        $tokenList->consumeKeywords(Keyword::ALTER, Keyword::USER);
        $ifExists = (bool) $tokenList->mayConsumeKeywords(Keyword::IF, Keyword::EXISTS);

        if ($tokenList->mayConsumeKeyword(Keyword::USER)) {
            $tokenList->consume(TokenType::LEFT_PARENTHESIS);
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
            $tokenList->consumeKeywords(Keyword::IDENTIFIED, Keyword::BY);
            $password = $tokenList->consumeString();

            return new AlterCurrentUserCommand($password, $ifExists);
        } elseif ($tokenList->seekKeyword(Keyword::DEFAULT, 4)) {
            $user = new UserName(...$tokenList->consumeUserName());
            $tokenList->consumeKeywords(Keyword::DEFAULT, Keyword::ROLE);
            $role = $this->parseRoleSpecification($tokenList);

            return new AlterUserDefaultRoleCommand($user, $role, $ifExists);
        }

        $users = $this->parseIdentifiedUsers($tokenList);
        $tlsOptions = $this->parseTlsOptions($tokenList);
        $resourceOptions = $this->parseResourceOptions($tokenList);
        $passwordLockOptions = $this->parsePasswordLockOptions($tokenList);

        $tokenList->expectEnd();

        return new AlterUserCommand($users, $tlsOptions, $resourceOptions, $passwordLockOptions, $ifExists);
    }

    /**
     * CREATE USER [IF NOT EXISTS]
     *     user [auth_option] [, user [auth_option]] ...
     *     DEFAULT ROLE role [, role ] ...
     *     [REQUIRE {NONE | tls_option [[AND] tls_option] ...}]
     *     [WITH resource_option [resource_option] ...]
     *     [password_option | lock_option] ...
     */
    public function parseCreateUser(TokenList $tokenList): CreateUserCommand
    {
        $tokenList->consumeKeywords(Keyword::CREATE, Keyword::USER);
        $ifNotExists = (bool) $tokenList->mayConsumeKeywords(Keyword::IF, Keyword::NOT, Keyword::EXISTS);

        $users = $this->parseIdentifiedUsers($tokenList);

        $tokenList->consumeKeywords(Keyword::DEFAULT, Keyword::ROLE);
        $defaultRoles = $this->parseRolesList($tokenList);

        $tlsOptions = $this->parseTlsOptions($tokenList);
        $resourceOptions = $this->parseResourceOptions($tokenList);
        $passwordLockOptions = $this->parsePasswordLockOptions($tokenList);

        $tokenList->expectEnd();

        return new CreateUserCommand($users, $defaultRoles, $tlsOptions, $resourceOptions, $passwordLockOptions, $ifNotExists);
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
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return \SqlFtw\Sql\Dal\User\IdentifiedUser[]
     */
    private function parseIdentifiedUsers(TokenList $tokenList): array
    {
        $users = [];
        do {
            $user = new UserName(...$tokenList->consumeUserName());
            if ($tokenList->mayConsumeKeywords(Keyword::DISCARD, Keyword::OLD, Keyword::PASSWORD)) {
                $action = IdentifiedUserAction::DISCARD_OLD_PASSWORD;
                $users[] = new IdentifiedUser($user, IdentifiedUserAction::get($action));
                continue;
            }

            if (!$tokenList->mayConsumeKeyword(Keyword::IDENTIFIED)) {
                $users[] = new IdentifiedUser($user);
                continue;
            }

            $action = $plugin = $password = $replace = null;
            $retainCurrent = false;
            if ($tokenList->mayConsumeKeyword(Keyword::WITH)) {
                $action = IdentifiedUserAction::SET_PLUGIN;
                $plugin = $tokenList->consumeName();
                if ($tokenList->mayConsumeKeyword(Keyword::AS)) {
                    $action = IdentifiedUserAction::SET_HASH;
                    $password = $tokenList->consumeString();
                }
            }
            if ($action !== IdentifiedUserAction::SET_HASH && $tokenList->mayConsumeKeyword(Keyword::BY)) {
                $action = IdentifiedUserAction::SET_PASSWORD;
                $password = $tokenList->consumeString();
                if ($tokenList->mayConsumeKeyword(Keyword::REPLACE)) {
                    $replace = $tokenList->consumeString();
                }
                if ($tokenList->mayConsumeKeywords(Keyword::RETAIN, Keyword::CURRENT, Keyword::PASSWORD)) {
                    $retainCurrent = true;
                }
            }
            $users[] = new IdentifiedUser($user, IdentifiedUserAction::get($action), $password, $plugin, $replace, $retainCurrent);
        } while ($tokenList->mayConsumeComma());

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
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return \SqlFtw\Sql\Dal\User\UserTlsOption[]|null
     */
    private function parseTlsOptions(TokenList $tokenList): ?array
    {
        $tlsOptions = null;
        if ($tokenList->mayConsumeKeyword(Keyword::REQUIRE)) {
            if ($tokenList->mayConsumeKeyword(Keyword::NONE)) {
                $tlsOptions = [];
            } else {
                $tlsOptions = [];
                do {
                    /** @var \SqlFtw\Sql\Dal\User\UserTlsOptionType $type */
                    $type = $tokenList->consumeKeywordEnum(UserTlsOptionType::class);
                    $value = $tokenList->mayConsumeString();
                    $tlsOptions[] = new UserTlsOption($type, $value);

                    if (!$tokenList->mayConsumeKeyword(Keyword::AND)) {
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
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return \SqlFtw\Sql\Dal\User\UserResourceOption[]|null
     */
    private function parseResourceOptions(TokenList $tokenList): ?array
    {
        if (!$tokenList->mayConsumeKeyword(Keyword::WITH)) {
            return null;
        }

        $resourceOptions = [];
        /** @var \SqlFtw\Sql\Dal\User\UserResourceOptionType $type */
        $type = $tokenList->consumeKeywordEnum(UserResourceOptionType::class);
        while ($type !== null) {
            $value = $tokenList->consumeInt();
            $resourceOptions[] = new UserResourceOption($type, $value);
            $type = $tokenList->mayConsumeKeywordEnum(UserResourceOptionType::class);
        }

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
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return \SqlFtw\Sql\Dal\User\UserPasswordLockOption[]|null
     */
    private function parsePasswordLockOptions(TokenList $tokenList): ?array
    {
        $passwordLockOptions = null;
        while ($keyword = $tokenList->mayConsumeAnyKeyword(Keyword::PASSWORD, Keyword::ACCOUNT)) {
            if ($keyword === Keyword::ACCOUNT) {
                $keyword = $tokenList->consumeAnyKeyword(Keyword::LOCK, Keyword::UNLOCK);
                $passwordLockOptions[] = new UserPasswordLockOption(UserPasswordLockOptionType::get(UserPasswordLockOptionType::ACCOUNT), $keyword);
                continue;
            }

            $keyword = $tokenList->consumeAnyKeyword(Keyword::EXPIRE, Keyword::HISTORY, Keyword::REUSE, Keyword::REQUIRE);
            if ($keyword === Keyword::EXPIRE) {
                $value = $tokenList->mayConsumeAnyKeyword(Keyword::DEFAULT, Keyword::NEVER, Keyword::INTERVAL);
                if ($value === Keyword::INTERVAL) {
                    $value = $tokenList->consumeInt();
                    $tokenList->consumeKeyword(Keyword::DAY);
                }
                $passwordLockOptions[] = new UserPasswordLockOption(UserPasswordLockOptionType::get(UserPasswordLockOptionType::PASSWORD_EXPIRE), $value);
            } elseif ($keyword === Keyword::HISTORY) {
                $value = Keyword::DEFAULT;
                if (!$tokenList->mayConsumeKeyword(Keyword::DEFAULT)) {
                    $value = $tokenList->consumeInt();
                }
                $passwordLockOptions[] = new UserPasswordLockOption(UserPasswordLockOptionType::get(UserPasswordLockOptionType::PASSWORD_HISTORY), $value);
            } elseif ($keyword === Keyword::REUSE) {
                $tokenList->consumeKeyword(Keyword::INTERVAL);
                $value = Keyword::DEFAULT;
                if (!$tokenList->mayConsumeKeyword(Keyword::DEFAULT)) {
                    $value = $tokenList->consumeInt();
                    $tokenList->consumeKeyword(Keyword::DAY);
                }
                $passwordLockOptions[] = new UserPasswordLockOption(UserPasswordLockOptionType::get(UserPasswordLockOptionType::PASSWORD_REUSE_INTERVAL), $value);
            } else {
                $tokenList->consumeKeyword(Keyword::CURRENT);
                $value = $tokenList->mayConsumeAnyKeyword(Keyword::DEFAULT, Keyword::OPTIONAL);
                $passwordLockOptions[] = new UserPasswordLockOption(UserPasswordLockOptionType::get(UserPasswordLockOptionType::PASSWORD_REQUIRE_CURRENT), $value);
            }
        }

        return $passwordLockOptions;
    }

    /**
     * CREATE ROLE [IF NOT EXISTS] role [, role ] ...
     */
    public function parseCreateRole(TokenList $tokenList): CreateRoleCommand
    {
        $tokenList->consumeKeywords(Keyword::CREATE, Keyword::ROLE);
        $ifNotExists = (bool) $tokenList->mayConsumeKeywords(Keyword::IF, Keyword::NOT, Keyword::EXISTS);
        $roles = $this->parseRolesList($tokenList);
        $tokenList->expectEnd();

        return new CreateRoleCommand($roles, $ifNotExists);
    }

    /**
     * DROP ROLE [IF EXISTS] role [, role ] ...
     */
    public function parseDropRole(TokenList $tokenList): DropRoleCommand
    {
        $tokenList->consumeKeywords(Keyword::DROP, Keyword::ROLE);
        $ifExists = (bool) $tokenList->mayConsumeKeywords(Keyword::IF, Keyword::EXISTS);
        $roles = $this->parseRolesList($tokenList);
        $tokenList->expectEnd();

        return new DropRoleCommand($roles, $ifExists);
    }

    /**
     * DROP USER [IF EXISTS] user [, user] ...
     */
    public function parseDropUser(TokenList $tokenList): DropUserCommand
    {
        $tokenList->consumeKeywords(Keyword::DROP, Keyword::USER);
        $ifExists = (bool) $tokenList->mayConsumeKeywords(Keyword::IF, Keyword::EXISTS);
        $users = $this->parseUserList($tokenList);
        $tokenList->expectEnd();

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
        $tokenList->consumeKeyword(Keyword::GRANT);

        if ($tokenList->mayConsumeKeywords(Keyword::PROXY, Keyword::ON)) {
            $proxy = new UserName(...$tokenList->consumeUserName());
            $tokenList->consumeKeyword(Keyword::TO);
            $users = $this->parseUserList($tokenList);
            $withGrantOption = (bool) $tokenList->mayConsumeKeywords(Keyword::WITH, Keyword::GRANT, Keyword::OPTION);
            $tokenList->expectEnd();

            return new GrantProxyCommand($proxy, $users, $withGrantOption);
        } elseif (!$tokenList->seekKeyword(Keyword::ON, 1000)) {
            $roles = $this->parseRolesList($tokenList);
            $tokenList->consumeKeyword(Keyword::TO);
            $users = $this->parseUserList($tokenList);
            $withAdminOption = (bool) $tokenList->mayConsumeKeywords(Keyword::WITH, Keyword::ADMIN, Keyword::OPTION);
            $tokenList->expectEnd();

            return new GrantRoleCommand($roles, $users, $withAdminOption);
        } else {
            $privileges = $this->parsePrivilegesList($tokenList);
            $resource = $this->parseResource($tokenList);
            $tokenList->consumeKeyword(Keyword::TO);
            $users = $this->parseIdentifiedUsers($tokenList);
            // 5.x only
            $tlsOptions = $this->parseTlsOptions($tokenList);
            $withGrantOption = (bool) $tokenList->mayConsumeKeywords(Keyword::WITH, Keyword::GRANT, Keyword::OPTION);
            // 5.x only
            $resourceOptions = $this->parseResourceOptions($tokenList);
            $as = $role = null;
            if ($tokenList->mayConsumeKeyword(Keyword::AS)) {
                $as = new UserName(...$tokenList->consumeUserName());
                if ($tokenList->mayConsumeKeywords(Keyword::WITH, Keyword::ROLE)) {
                    $role = $this->parseRoleSpecification($tokenList);
                }
            }
            $tokenList->expectEnd();

            return new GrantCommand($privileges, $resource, $users, $as, $role, $tlsOptions, $resourceOptions, $withGrantOption);
        }
    }

    /**
     * priv_type [(column_list)] [, priv_type [(column_list)]] ...
     *
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return \SqlFtw\Sql\Dal\User\UserPrivilege[]
     */
    private function parsePrivilegesList(TokenList $tokenList): array
    {
        $privileges = [];
        do {
            $types = UserPrivilegeType::getFistAndSecondKeywords();
            $type = $tokenList->consumeAnyKeyword(...array_keys($types));
            $next = $types[$type];
            if ($type === Keyword::ALL) {
                $tokenList->mayConsumeKeyword(Keyword::PRIVILEGES);
                $next = null;
            } elseif ($type === Keyword::CREATE) {
                $next = $tokenList->mayConsumeAnyKeyword(...$next);
                if ($next === Keyword::TEMPORARY) {
                    $tokenList->consumeKeyword(Keyword::TABLES);
                    $next .= ' ' . Keyword::TABLES;
                }
            } elseif ($type === Keyword::ALTER) {
                $next = $tokenList->mayConsumeAnyKeyword(...$next);
            } elseif ($next !== null) {
                $next = $tokenList->consumeAnyKeyword(...$next);
            }
            if ($next !== null) {
                $type .= ' ' . $next;
            }

            $columns = null;
            if ($tokenList->mayConsume(TokenType::LEFT_PARENTHESIS)) {
                $columns = [];
                do {
                    $columns[] = $tokenList->consumeName();
                } while ($tokenList->mayConsumeComma());
                $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
            }
            $privileges[] = new UserPrivilege(UserPrivilegeType::get($type), $columns);
        } while ($tokenList->mayConsumeComma());

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
        $tokenList->consumeKeyword(Keyword::ON);
        /** @var \SqlFtw\Sql\Dal\User\UserPrivilegeResourceType|null $resourceType */
        $resourceType = $tokenList->mayConsumeKeywordEnum(UserPrivilegeResourceType::class);
        if ($tokenList->mayConsumeOperator(Operator::MULTIPLY)) {
            $object = false;
            if ($tokenList->mayConsume(TokenType::DOT)) {
                $tokenList->consumeOperator(Operator::MULTIPLY);
                $object = true;
            }
            return new UserPrivilegeResource(UserPrivilegeResource::ALL, $object ? UserPrivilegeResource::ALL : null, $resourceType);
        } else {
            $name = $tokenList->consumeName();
            if ($tokenList->mayConsume(TokenType::DOT)) {
                $database = $name;
                if ($tokenList->mayConsumeOperator(Operator::MULTIPLY)) {
                    return new UserPrivilegeResource($database, UserPrivilegeResource::ALL, $resourceType);
                } else {
                    $name = $tokenList->consumeName();
                    return new UserPrivilegeResource($database, $name, $resourceType);
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
        $tokenList->consumeKeywords(Keyword::RENAME, Keyword::USER);

        $users = [];
        $newUsers = [];
        do {
            $users[] = new UserName(...$tokenList->consumeUserName());
            $tokenList->consumeKeyword(Keyword::TO);
            $newUsers[] = new UserName(...$tokenList->consumeUserName());
        } while ($tokenList->mayConsumeComma());

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
        $tokenList->consumeKeyword(Keyword::REVOKE);

        if ($tokenList->mayConsumeKeyword(Keyword::ALL)) {
            $tokenList->mayConsumeKeyword(Keyword::PRIVILEGES);
            $tokenList->consume(TokenType::COMMA);
            $tokenList->consumeKeywords(Keyword::GRANT, Keyword::OPTION, Keyword::FROM);
            $users = $this->parseUserList($tokenList);
            $tokenList->expectEnd();

            return new RevokeAllCommand($users);
        } elseif ($tokenList->mayConsumeKeywords(Keyword::PROXY)) {
            $tokenList->consumeKeyword(Keyword::ON);
            $proxy = new UserName(...$tokenList->consumeUserName());
            $tokenList->consumeKeyword(Keyword::FROM);
            $users = $this->parseUserList($tokenList);
            $tokenList->expectEnd();

            return new RevokeProxyCommand($proxy, $users);
        } elseif ($tokenList->seekKeyword(Keyword::ON, 1000)) {
            $privileges = $this->parsePrivilegesList($tokenList);
            $resource = $this->parseResource($tokenList);
            $tokenList->consumeKeyword(Keyword::FROM);
            $users = $this->parseUserList($tokenList);
            $tokenList->expectEnd();

            return new RevokeCommand($privileges, $resource, $users);
        } else {
            $roles = $this->parseRolesList($tokenList);
            $tokenList->consumeKeyword(Keyword::FROM);
            $users = $this->parseUserList($tokenList);
            $tokenList->expectEnd();

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
        $tokenList->consumeKeywords(Keyword::SET, Keyword::DEFAULT, Keyword::ROLE);
        /** @var \SqlFtw\Sql\Dal\User\UserDefaultRolesSpecification|null $roles */
        $roles = $tokenList->mayConsumeKeywordEnum(UserDefaultRolesSpecification::class);
        $rolesList = null;
        if ($roles === null) {
            $rolesList = $this->parseRolesList($tokenList);
        }

        $tokenList->consumeKeyword(Keyword::TO);
        $users = $this->parseUserList($tokenList);
        $tokenList->expectEnd();

        return new SetDefaultRoleCommand($users, $roles, $rolesList);
    }

    /**
     * SET PASSWORD [FOR user] = password_option
     *
     * password_option: {
     *     PASSWORD('auth_string')
     *   | 'auth_string'
     * }
     */
    public function parseSetPassword(TokenList $tokenList): SetPasswordCommand
    {
        $tokenList->consumeKeywords(Keyword::SET, Keyword::PASSWORD);
        $user = null;
        if ($tokenList->mayConsumeKeyword(Keyword::FOR)) {
            $user = new UserName(...$tokenList->consumeUserName());
        }
        $tokenList->consumeOperator(Operator::EQUAL);
        $function = $tokenList->mayConsumeKeyword(Keyword::PASSWORD);
        if ($function !== null) {
            $tokenList->consume(TokenType::LEFT_PARENTHESIS);
        }
        $password = $tokenList->consumeString();
        if ($function !== null) {
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
        }
        $tokenList->expectEnd();

        return new SetPasswordCommand($user, $password, (bool) $function);
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
        $tokenList->consumeKeywords(Keyword::SET, Keyword::ROLE);
        $role = $this->parseRoleSpecification($tokenList);
        $tokenList->expectEnd();

        return new SetRoleCommand($role);
    }

    private function parseRoleSpecification(TokenList $tokenList): RolesSpecification
    {
        $keyword = $tokenList->mayConsumeAnyKeyword(Keyword::DEFAULT, Keyword::NONE, Keyword::ALL);
        $except = null;
        if ($keyword !== null) {
            if ($keyword === Keyword::ALL) {
                $except = $tokenList->mayConsumeKeyword(Keyword::EXCEPT);
            }
        }
        $type = $keyword
            ? ($except ? RolesSpecificationType::ALL_EXCEPT : $keyword)
            : RolesSpecificationType::LIST;

        $roles = null;
        if ($except !== null || $keyword === null) {
            $roles = $this->parseRolesList($tokenList);
        }

        return new RolesSpecification(RolesSpecificationType::get($type), $roles);
    }

    /**
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return \SqlFtw\Sql\UserName[]
     */
    private function parseUserList(TokenList $tokenList): array
    {
        $users = [];
        do {
            $users[] = new UserName(...$tokenList->consumeUserName());
        } while ($tokenList->mayConsumeComma());

        return $users;
    }

    /**
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return string[]
     */
    private function parseRolesList(TokenList $tokenList): array
    {
        $roles = [];
        do {
            $roles[] = $tokenList->consumeName();
        } while ($tokenList->mayConsumeComma());

        return $roles;
    }

}
