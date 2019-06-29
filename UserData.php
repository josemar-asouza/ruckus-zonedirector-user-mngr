<?php

/**
 * Created by PhpStorm.
 * User: RF511
 * Date: 05/04/2017
 * Time: 17:23
 */
class UserData
{
    public $userName;
    public $fullName;
    public $role;
    public $password;

    /**
     * user constructor.
     * @param $userName
     * @param $fullName
     * @param $password
     * @param $role
     */
    public function __construct($id, $userName, $fullName, $password, $role)
    {
        $this->id = $id;
        $this->userName = $userName;
        $this->fullName = $fullName;
        $this->password = $password;
        $this->role = $role;
    }

    /**
     * @return mixed
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param mixed $userName
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;
    }

    /**
     * @return mixed
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * @param mixed $fullName
     */
    public function setFullName($fullName)
    {
        $this->fullName = $fullName;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param mixed $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

}