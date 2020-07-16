<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="`user`")
 * @UniqueEntity(message="Данный e-mail занят", fields={"email"})
 * @UniqueEntity(message="Данный ник занят", fields={"username"})
 */
class User implements UserInterface
{
    public const ROLE_USER = 'ROLE_USER';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"public"})
     */
    private $id;

    /**
     * @Assert\Length(min="5", minMessage="Минимальное количество символов - 5")
     * @Assert\Regex(pattern="/^[a-zA-Z0-9_]+$/", message="Ник должен содержать в себе цифры и/или буквы на латинице")
     * @Assert\NotBlank(message="Ник не заполнен")
     * @ORM\Column(type="string", length=100, unique=true)
     * @Groups({"public"})
     */
    private $username;

    /**
     * @Assert\Length(min="5", minMessage="Минимальное количество символов - 5")
     * @Assert\Email(message="E-mail неправильного формата")
     * @Assert\NotBlank(message="E-mail не заполнен")
     * @ORM\Column(type="string", length=180, unique=true)
     * @Groups({"public"})
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * TODO вырезать кириллицу
     * @var string The hashed password
     * @Assert\Regex(pattern="/^(?=.*[a-z])(?=.*\d).{8,}$/i", message="Пароль должен содержать в себе цифры и буквы")
     * @Assert\Length(min="8", minMessage="Минимальное количество символов - 8")
     * @Assert\NotBlank(message="Пароль не заполнен")
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $confirmationCode;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $enabled;


    public function __construct()
    {
        $this->roles = [self::ROLE_USER];
        $this->enabled = false;
    }


    /**
     * @return string
     */
    public function getConfirmationCode(): string
    {
        return $this->confirmationCode;
    }

    /**
     * @param string $confirmationCode
     */
    public function setConfirmationCode(string $confirmationCode): void
    {
        $this->confirmationCode = $confirmationCode;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username): void
    {
        $this->username = $username;
    }
}
