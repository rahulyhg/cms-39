<?php namespace Gzero\Entity;

use Illuminate\Auth\Reminders\RemindableInterface;
use Illuminate\Auth\UserInterface;
use Illuminate\Hashing\BcryptHasher;

/**
 * This file is part of the GZERO CMS package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Class BaseUser
 *
 * @package    Gzero\Entity
 * @author     Adrian Skierniewski <adrian.skierniewski@gmail.com>
 * @copyright  Copyright (c) 2014, Adrian Skierniewski
 * @MappedSuperclass
 */
class BaseUser implements UserInterface, RemindableInterface {

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var integer
     */
    protected $id;

    /**
     * @Column(type="string", unique=TRUE)
     * @var string
     */
    protected $email;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $firstName;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $lastName;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $password;

    /**
     * @Column(type="string", nullable=TRUE)
     * @var string
     */
    protected $rememberToken = null;

    /**
     * Get entity id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user email
     *
     * @param string $email Email address
     *
     * @return void
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Get email address
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set user first name
     *
     * @param string $firstName User first name
     *
     * @return void
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * Get user first name
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set user last name
     *
     * @param string $lastName User last name
     *
     * @return void
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * Get user last name
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set user Password
     *
     * @param string $password User password
     *
     * @return void
     */
    public function setPassword($password)
    {
        // $this->password = Hash::make($password);
        $hasher         = new BcryptHasher();
        $this->password = $hasher->make($password);
    }

    /**
     * Get user password in hashed form
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set user remember token
     *
     * @param string $token Token value
     *
     * @return void
     */
    public function setRememberToken($token)
    {
        $this->rememberToken = $token;
    }

    /**
     * Get remember token
     *
     * @return string
     */
    public function getRememberToken()
    {
        return $this->rememberToken;
    }


    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'rememberToken';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->getId();
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->getPassword();
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getAuthRememberToken()
    {
        return $this->getRememberToken();
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param string $value Token value
     *
     * @return void
     */
    public function setAuthRememberToken($value)
    {
        $this->setRememberToken($value);
    }

    /**
     * Get the e-mail address where password reminders are sent.
     *
     * @return string
     */
    public function getReminderEmail()
    {
        return $this->getEmail();
    }
}