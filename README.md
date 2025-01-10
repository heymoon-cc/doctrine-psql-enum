# Doctrine enums for PostgreSQL
[![Version](https://poser.pugx.org/heymoon/doctrine-psql-enum/v)](https://packagist.org/packages/heymoon/doctrine-psql-enum)
[![PHP Version Require](https://poser.pugx.org/heymoon/doctrine-psql-enum/require/php)](https://packagist.org/packages/heymoon/doctrine-psql-enum)
[![Test](https://github.com/heymoon-cc/doctrine-psql-enum/actions/workflows/test.yaml/badge.svg)](https://github.com/heymoon-cc/doctrine-psql-enum/actions/workflows/test.yaml)
## Prerequisites: *Symfony 7 + Doctrine 3*

### Installation
`composer require heymoon/doctrine-psql-enum`

### Usage
Create library configuration:

`config/packages/doctrine_postgres_enum.yaml`
```yaml
doctrine_postgres_enum:
  type_name: enum
  migrations:
    enabled: true
    comment_tag: DC2Enum
```
For defining new enum type, [use native PHP enums](https://www.php.net/manual/language.types.enumerations.php):
```php
use HeyMoon\DoctrinePostgresEnum\Attribute\EnumType;

#[EnumType('auth_status')]
enum AuthStatus: string
{
    case New = 'new';
    case Active = 'active';
    case Inactive = 'inactive';
    case Deleted = 'deleted';
}

#[EnumType('auth_service')]
enum Service: string
{
    case Google = 'google';
}
```
For creation of enum-field in model, use `enum` as `type` value, `enumType` in `Column` attribute must be defined:
```php
#[ORM\Entity(repositoryClass: AuthRepository::class)]
class Auth
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: "doctrine.uuid_generator")]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'enum', enumType: AuthStatus::class)]
    private AuthStatus $status;

    #[ORM\Column(type: 'enum', enumType: Service::class)]
    private Service $service;
}
```
Create migrations via `make:migration`. If enum was created or modified, the `CREATE TYPE`/`ALTER TYPE` calls would be added to migration. Example:
```php
$this->addSql('DROP TYPE IF EXISTS auth_status');
$this->addSql('CREATE TYPE auth_status AS ENUM (\'new\',\'active\',\'inactive\',\'deleted\')');
$this->addSql('DROP TYPE IF EXISTS auth_service');
$this->addSql('CREATE TYPE auth_service AS ENUM (\'google\')');
$this->addSql('CREATE TABLE auth (id UUID NOT NULL, status auth_status NOT NULL, service auth_service NOT NULL, PRIMARY KEY(id))');
```
