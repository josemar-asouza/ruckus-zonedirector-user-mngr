<?php

error_reporting(E_ALL ^ E_WARNING);
libxml_use_internal_errors(true);
date_default_timezone_set('America/Sao_Paulo');

//Atualizar as regras abaixo de acordo com as regras do firmware rodando no equipamento
define("UserNamePattern", '/^[a-zA-Z|\.|_]{1,1}[a-zA-Z0-9|\.|_]{0,31}$/');
define("FullNamePattern", '/^[\x20\x30-\x39\x40-\x7A]{0,34}$/');
define("PasswordPattern", '/^[\x21-\x7E]{4,32}$/');

include("UserData.php");

class ZoneDirectorUserManager {
	//Caso use o Bitvise SSH Server, o userName é o seu nome do usuário do Windows
	//e o password a sua senha do Windows
    
	//Atualize essas variáveis para combinar com as configurações do equipamento
    //Update these variables to match the device settings 
    private $sshUsername = '';
    private $sshPassword = '';
    private $sshHost = '127.0.0.1';
	    
    private $sshPort = 22;
    private $logPath = __DIR__ . '/logs/';
    //Defina a variável abaixo para "pt" para gerar os logs em português
    //Set the variable below to "en" to generate logs in english
    private $logLanguage = 'pt';
    
    private $messages = array(
        "pt" => array(
            "InfoSSHConnected" => "Conexão SSH estabelecida",
            "InfoUsersListed"  => "Usuários listados",
            "InfoRolesListed"  => "Regras listadas",
            
            "ErrorUndefined"   => "Erro não definido",
            "ErrorSshConnect"  => "Não é possível estabelecer conexão SSH",
            "ErrorSshPassword" => "Nome do usuário ou senha do SSH incorreto",
            "ErrorGetUserData" => "Erro ao carregar dados dos usuário. Por favor tente novamente",
            
            "ErrorJsonNoData"        => "Nenhum dado encontrado no JSON",
            "ErrorJsonInvalidFormat" => "Formato do JSON inválido",
            "ErrorXmlInvalidFormat"  => "Formato do XML inválido",
            "ErrorIncompletData"     => 'Índice "%d": dados do usuário está incompleto',
            "ErrorUserElemNotFound"  => 'Índice "%d": elemento "user" não encontrado',
            "ErrorElementsNotFound"  => 'Índice "%d": elementos(s) "userName" e/ ou "fullName" e/ ou "password" e/ ou "role" não encontrado(s)',
            "ErrorJsonKeysNotFound"  => 'Índice "%d": chaves(s) "userName" e/ ou "fullName" e/ ou "password" e/ ou "role" não encontrado(s)',
            "BackupCreated"          => 'Backup "%s" criado com sucesso ',
            
            "LogUserCreated"            => '(adicionar) usuário "%s": criado com sucesso',
            "ErrorInvalidNameFormat"    => '(adicionar) usuário "%s": nome do usuário não satisfaz os requisitos do dispositivo',
            "ErrorInvalidNameInUse"     => '(adicionar) usuário "%s": nome do usuário já está em uso',
            "ErrorInvalidFullNameAdd"   => '(adicionar) usuário "%s": nome completo do usuário não satisfaz os requisitos do dispositivo',
            "ErrorInvalidPasswordAdd"   => '(adicionar) usuário "%s": senha do usuário não satisfaz os requisitos do dispositivo',
            "ErrorInvalidRoleAdd"       => '(adicionar) usuário "%s": regra do usuário "%s" não existe na base de dados',
            
            "ErrorInvalidNewNameFormat" => '(atualizar) usuário "%s": novo nome do usuário "%s" não satisfaz os requisitos do dispositivo',
            "ErrorInvalidNewNameInUse"  => '(atualizar) usuário "%s": novo nome do usuário "%s" já está em uso',
            "ErrorInvalidFullNameUpt"   => '(atualizar) usuário "%s": nome completo do usuário não satisfaz os requisitos do dispositivo',
            "ErrorInvalidPasswordUpt"   => '(atualizar) usuário "%s": senha do usuário não satisfaz os requisitos do dispositivo',
            "ErrorInvalidRoleUpt"       => '(atualizar) usuário "%s": regra do usuário "%s" não existe na base de dados',
            "ErrorUserNotFoundUpt"      => '(atualizar) usuário "%s": nome do usuário não existe na base de dados',
            "LogUserNameUpdated"        => '(atualizar) usuário "%s": nome do usuário atualizado para "%s"',
            "LogFullNameUpdated"        => '(atualizar) usuário "%s": nome completo atualizado de "%s" para "%s"',
            "LogPasswordUpdated"        => '(atualizar) usuário "%s": senha atualizada para "%s"',
            "LogUserRoleUpdated"        => '(atualizar) usuário "%s": regra atualizada de "%s" para "%s"',
            "LogUserUpdated"            => '(atualizar) usuário "%s": atualizado com sucesso',
            "LogUserNotUpdated"         => '(atualizar) usuário "%s": atualização não foi realizada',
            
            "LogUserDeleted"            => '(excluir) usuário "%s": excluido com sucesso',
            "ErrorUserNotFoundDel"      => '(excluir) usuário "%s": nome do usuário não existe na base de dados',
            "ErrorRoleNotFound"         => '(excluir) usuário "*": regra "%s" não existe na base de dados',
        ),
        "en" => array(
            "InfoSSHConnected" => "SSH connection established",
            "InfoUsersListed"  => "Users was listed",
            "InfoRolesListed"  => "Roles was listed",
            
            "ErrorUndefined"   => "Undefined error",
            "ErrorSshConnect"  => "Unable to establish SSH connection",
            "ErrorSshPassword" => "Wrong SSH username or password",
            "ErrorGetUserData" => "Error while loading users data. Please try again",
            
            "ErrorJsonNoData"        => "No data found in JSON",
            "ErrorJsonInvalidFormat" => "Invalid JSON format",
            "ErrorXmlInvalidFormat"  => "Invalid XML format",
            "ErrorIncompletData"     => 'Index "%d": user data is incomplete',
            "ErrorUserElemNotFound"  => 'Index "%d": element "user" not found',
            "ErrorElementsNotFound"  => 'Index "%d": element(s) "userName" and/ or "fullName" and/ or "password" and/ or "role" not found',
            "ErrorJsonKeysNotFound"  => 'Index "%d": key(s) "userName" and/ or "fullName" and/ or "password" and/ or "role" not found',
            "BackupCreated"          => 'Backup "%s" successfully created',
            
            "LogUserCreated"            => '(add) user "%s": successfully created',
            "ErrorInvalidNameFormat"    => '(add) user "%s": user name does not meet device requirements',
            "ErrorInvalidNameInUse"     => '(add) user "%s": user name is already in use',
            "ErrorInvalidFullNameAdd"   => '(add) user "%s": user full name does not meet device requirements',
            "ErrorInvalidPasswordAdd"   => '(add) user "%s": user password does not meet device requirements',
            "ErrorInvalidRoleAdd"       => '(add) user "%s": user role "%s" does not exists in database',
            
            "ErrorInvalidNewNameFormat" => '(update) user "%s": new user name "%s" does not meet device requirements',
            "ErrorInvalidNewNameInUse"  => '(update) user "%s": new user name "%s" is already in use',
            "ErrorInvalidFullNameUpt"   => '(update) user "%s": user full name does not meet device requirements',
            "ErrorInvalidPasswordUpt"   => '(update) user "%s": user password does not meet device requirements',
            "ErrorInvalidRoleUpt"       => '(update) user "%s": user role "%s" does not exists in database',
            "ErrorUserNotFoundUpt"      => '(update) user "%s": user name does not exists in database',
            "LogUserNameUpdated"        => '(update) user "%s": user name updated from "%s" to "%s"',
            "LogFullNameUpdated"        => '(update) user "%s": full name updated from "%s" to "%s"',
            "LogPasswordUpdated"        => '(update) user "%s": password updated to "%s"',
            "LogUserRoleUpdated"        => '(update) user "%s": role updated from "%s" to "%s"',
            "LogUserUpdated"            => '(update) user "%s": successfully updated',
            "LogUserNotUpdated"         => '(update) user "%s": update was not performed',
            
            "LogUserDeleted"            => '(delete) user "%s": successfully deleted',
            "ErrorUserNotFoundDel"      => '(delete) user "%s": user name does not exists in database',
            "ErrorRoleNotFound"         => '(delete) user "*": role "%s" does not exists in database',
        )
    );
    private $connection;
    private $userList = array();
    private $roleList = array();
    private $goodToGo;
    private $lastError = "ErrorUndefined";
    private $lastErrorArgs = null;
    private $actionLog = array();
    private $logTxt = "";
    private $stream;
    private $isSimulator;

    public function __construct() {
        $this->connectSSH();
    }

    function __destruct() {
        $this->disconnectSSH();
        $this->saveLog();
    }
    
    private function consoleToLog($msg) {
        $myfile = fopen('console.txt', "a") or die("Unable to open file!");

        fwrite($myfile, $msg . PHP_EOL . "***********************" . PHP_EOL);
        fclose($myfile);
    }

    private function saveLog(){
        $path = substr($this->logPath, 0, -1);
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $myfile = fopen($this->logPath . "ZDUM_" . date("Y-m-d") . ".txt", "a") or die("Unable to open file!");
        fwrite($myfile, $this->logTxt);
        fclose($myfile);
    }
    
    private function toLog($type, $args = null) {
        if ($args !== null){
            $this->actionLog[] = array ("type" => $type, "args" => $args);
        }
		if (strpos($type, 'Error') === 0) {
            $this->lastError = $type;
			$this->lastErrorArgs = $args;
        }

        $msg = $this->messages[$this->logLanguage][$type];
        $msg = vsprintf($msg, $args);
        $this->logTxt .= date("H:i:s") . " - " . $msg . PHP_EOL;
    }
	
	private function removeAccents($text){
		$from = array('Á','Í','Ó','Ú','É','Ä','Ï','Ö','Ü','Ë','À','Ì','Ò','Ù','È','Ã','Õ','Â','Î','Ô','Û','Ê','á','í','ó','ú','é','ä','ï','ö','ü','ë','à','ì','ò','ù','è','ã','õ','â','î','ô','û','ê','Ç','ç','Ñ','ñ');
		$to = array('A','I','O','U','E','A','I','O','U','E','A','I','O','U','E','A','O','A','I','O','U','E','a','i','o','u','e','a','i','o','u','e','a','i','o','u','e','a','o','a','i','o','u','e','C','c','N','n');
		return str_replace($from,$to,$text);
	}
	
//----------------- General -----------------\\
 
    public function getLastError($language = "pt") {
        $msg = $this->messages[$language][$this->lastError];
		$msg = vsprintf($msg, $this->lastErrorArgs);
        return $msg;
    }

    public function getLog($language = "pt"){
        $resp = '';
        foreach ($this->actionLog as $toLog){
            $msg = $this->messages[$this->logLanguage][$toLog['type']];
            $resp .= vsprintf($msg, $toLog['args']) . PHP_EOL;
        }
        return $resp;
    }
    
    public function isGoodToGo() {
        return $this->goodToGo;
    }

//----------------- SSH Manager -----------------\\
    private function sshConnected (){
        $this->execCmd("enable force");
        $this->goodToGo = true;
        $this->toLog("InfoSSHConnected");
    }
    
    private function loginSimulator (){
        $this->isSimulator = true;
        $auth = ssh2_auth_password($this->connection, $this->sshUsername, $this->sshPassword);
        if ($auth) {
            $this->stream = ssh2_shell($this->connection, 'ansi', null, 110, 1, SSH2_TERM_UNIT_CHARS);
            $this->sshConnected();
        } else {		
            $this->toLog("ErrorSshPassword");
        }
    }
    
    private function loginZoneDirector (){
        $this->isSimulator = false;
        $this->stream = ssh2_shell($this->connection, 'ansi', null, 110, 1, SSH2_TERM_UNIT_CHARS);
        usleep(500000);
        $this->execCmd($this->sshUsername);
        $this->execCmd($this->sshPassword);
        usleep(500000);
        $data = '';
        while ($line = fgets($this->stream)) {
            flush();
            $data .= $line;
        }
        if (strpos($data, 'Welcome') !== false){
           $this->sshConnected();                    
        }
        else {
            $this->toLog("ErrorSshPassword");
        }
    }
    
    private function connectSSH() {
        $this->goodToGo = false;
        if (!($this->connection = ssh2_connect($this->sshHost, $this->sshPort))) {
            $this->toLog("ErrorSshConnect");
        } else {
            $auth = null;
            $auth_methods = ssh2_auth_none($this->connection, $this->sshUsername);
            if ($auth_methods === true) {
                $this->loginZoneDirector();
            } else {
                if (in_array('password', $auth_methods)) {
                    $this->loginSimulator();
                } else {
                    $this->toLog("ErrorSshConnect");
                }
            }

        }
    }

    private function disconnectSSH() {
        $this->execCmd('exit');
        fclose($this->stream);
        unset($this->connection);
    }

    private function execCmd($cmd) {
        fwrite($this->stream, $cmd . PHP_EOL);
    }

    private function execCmdBlock($cmds){
        $this->execCmd('config');
        $this->execCmd($cmds);
        $this->execCmd('exit');
        return $this->getSshConsoleTxt('ruckus(config)# Your changes have been saved', 120);		
    }
    
    private function getLastLines($string, $n = 1) {
        $lines = explode("\n", $string);
        $lines = array_slice($lines, -$n);
        return implode(PHP_EOL, $lines);
    }
    
    private function getSshConsoleTxt($stopString = 'ruckus#', $maxAttemps = 5) {
        $attemps = 0;
        $console = '';
        $data = '';
        $lastLines = "";
		$zeroLengthCount = 0;
        set_time_limit(0);
        do {
            sleep(1);
            $data = '';
            while ($line = fgets($this->stream)) {
                flush();
                $data .= $line;
            }
            $attemps++;
            $data = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", PHP_EOL, $data);
            $console .= $data;
			$lastLines = $this->getLastLines($data, 3);
			if (strlen($data) === 0){
				$zeroLengthCount++;
			}
			else
			{
				$zeroLengthCount = 0;
			}
        } while (($attemps < $maxAttemps) && ($zeroLengthCount<2) && (strpos($lastLines, $stopString) === false));
        set_time_limit(20);

        if ($this->isSimulator){
            $console = preg_replace('/\s{2,}|\[1;[0-9]+H/', '', $console);
            $console = preg_replace('/[]/', '', $console);                 
        }
        else {
            $console = preg_replace('/ {2,}/', '', $console);
        }         
        
        //$this->consoleToLog($console);
        return $console;
    }

//--------------------- Load Data -----------------------\\
    private function loadUsers() {
        $this->execCmd("show user all");
        $data = $this->getSshConsoleTxt();
        $lines = explode("\n", $data);
        $arr_length = count($lines);
        $this->userList = array();
        $i = 0;
        while ($i < $arr_length) {
            if (strpos($lines[$i], 'User Name') !== false) {
                $id = substr($lines[$i - 1], 0, strpos($lines[$i - 1], ';') - 1);
                $uName = substr($lines[$i], 11);
                $fName = substr($lines[$i + 1], 11);
                $fName = $fName === false ? '' : $fName;
                //$password = substr($lines[$i + 2], 10);
                $password = '';
                $role = substr($lines[$i + 3], 6);
                $this->userList[] = new UserData($id, $uName, $fName, $password, $role);
                $i += 3;
            } else {
                $i++;
            }
        }
        $this->toLog("InfoUsersListed");
    }

    private function loadRoles() {
        $this->execCmd("show role all");
        $data = $this->getSshConsoleTxt();
        $lines = explode("\n", $data);
        $arr_length = count($lines);
        $this->roleList = array();
        $i = 0;
        while ($i < $arr_length) {
            if (strpos($lines[$i], 'Name=') !== false) {
                $this->roleList[] = substr($lines[$i], 6);
                $i += 4;
            } else {
                $i++;
            }
        }
        $this->toLog("InfoRolesListed");
    }

//--------------------- List Data -----------------------\\
    private function convertUserData($userList, $returnType, $delimiterChar, $ignoreId = false) {
        if ($returnType === 'array') {
            return $userList;
        }
        if ($returnType === 'txt') {
            $txt = "";
            foreach ($userList as $user) {
                if (!$ignoreId) {
                    $txt .= $user->id . $delimiterChar;
                }
                $txt .= $user->userName . $delimiterChar;
                $txt .= $user->fullName . $delimiterChar;
                $txt .= $user->role . $delimiterChar;
                $txt .= $user->password . PHP_EOL;
            }
            return $txt;
        }
        if ($returnType === 'xml') {
            $xml = new SimpleXMLElement('<root/>');
            foreach ($userList as $user) {
                $el = $xml->addChild('user');
                if (!$ignoreId) {
                    $el->addChild('id', $user->id);
                }
                $el->addChild('userName', $user->userName);
                $el->addChild('fullName', $user->fullName);
                $el->addChild('role', $user->role);
                $el->addChild('password', $user->password);
            }

            return $xml->asXML();
        }
        if ($returnType === 'json') {
            if ($ignoreId) {
                foreach ($userList as $user) {
                    unset($user->id);
                }
            }
            return json_encode($userList);
        }
    }

    public function getUsersData($returnType = 'json', $delimiterChar = ';') {
        if ($this->goodToGo) {
            $this->loadUsers();
            return $this->convertUserData($this->userList, $returnType, $delimiterChar);
        } else {
            return false;
        }
    }

    public function getUserDataByName($userName, $returnType = 'json', $delimiterChar = ';') {
        if ($this->goodToGo) {
            $this->loadUsers();
            $userList = array();
            foreach ($this->userList as $user) {
                if ($userName === $user->userName) {
                    $userList[] = $user;
                }
            }
            return $this->convertUserData($userList, $returnType, $delimiterChar);
        } else {
            return false;
        }
    }

    public function getUsersDataByName($userName, $returnType = 'json', $delimiterChar = ';') {
        if ($this->goodToGo) {
            $this->loadUsers();
            $userList = array();
            foreach ($this->userList as $user) {
                if (preg_match('/.*' . $userName . '.*/i', $user->userName)) {
                    $userList[] = $user;
                }
            }
            return $this->convertUserData($userList, $returnType, $delimiterChar);
        } else {
            return false;
        }
    }

    public function getUsersDataByFullName($fullName, $returnType = 'json', $delimiterChar = ';') {
        if ($this->goodToGo) {
            $this->loadUsers();
            $userList = array();
            foreach ($this->userList as $user) {
                if (preg_match('/.*' . $fullName . '.*/i', $user->fullName)) {
                    $userList[] = $user;
                }
            }
            return $this->convertUserData($userList, $returnType, $delimiterChar);
        } else {
            return false;
        }
    }

    public function getUsersDataByRole($role, $returnType = 'json', $delimiterChar = ';') {
        if ($this->goodToGo) {
            $this->loadUsers();
            $userList = array();
            foreach ($this->userList as $user) {
                if ($user->role === $role) {
                    $userList[] = $user;
                }
            }
            return $this->convertUserData($userList, $returnType, $delimiterChar);
        } else {
            return false;
        }
    }

    public function getRoles($returnType = 'json', $delimiterChar = ';') {
        if (empty($this->roleList)) {
            $this->loadRoles();
        }
        if ($returnType === 'array') {
            return $this->roleList;
        }
        if ($returnType === 'txt') {
            return implode($delimiterChar, $this->roleList);
        }
        if ($returnType === 'xml') {
            $xml = new SimpleXMLElement('<root/>');
            foreach ($this->roleList as $role) {
                $el = $xml->addChild('role');
                $el->addChild('name', $role);
            }
            return $xml->asXML();
        }
        if ($returnType === 'json') {
            return json_encode($this->roleList);
        }
    }

//------------------- INSERT -----------------------\\
    
    private function generateAddLog($console){
        function name ($line){
            $i1 = strpos($line, "'")+1;
            $i2 = strpos($line, "'", $i1+1);
            return substr($line, $i1, ($i2 - $i1));
        }
        $lines = explode("\n", $console);
        foreach ($lines as $line){
            if (strpos($line, 'has been created') !== false){
                $this->toLog("LogUserCreated", array(name($line)));
            }
            if (strpos($line, 'has been loaded') !== false){
                $this->toLog("LogUserUpdated", array(name($line)));
            }
        }
    }
    
    private function addUser($returnCommand, $name, $fullName, $password, $role) {
        $fullName = $this->removeAccents($fullName);
		if (preg_match(UserNamePattern, $name)) {
            if (preg_match(PasswordPattern, $password)) {
                if (preg_match(FullNamePattern, $fullName)) {
                    if (array_search($role, $this->roleList) !== false) {
                        if ($returnCommand){                            
                            $cmds = 'user ' . $name . PHP_EOL .
                                "full-name '" . $fullName . "'" . PHP_EOL .
                                'password ' . $password . PHP_EOL .
                                'role ' . $role . PHP_EOL .
                                'exit' . PHP_EOL;
                            return $cmds;
                        }
                        else {
                            $this->execCmd("config");
                            $this->execCmd("user " . $name);
                            $this->execCmd("full-name '" . $fullName . "'");
                            $this->execCmd("password " . $password);
                            $this->execCmd("role " . $role);
                            $this->execCmd("exit" . PHP_EOL . "exit");
                            $this->toLog("LogUserCreated", array($name));
                            return true;
                        }
                    } else {
                        $this->toLog("ErrorInvalidRoleAdd", array($name, $role));
                    }
                } else {
                    $this->toLog("ErrorInvalidFullNameAdd", array($name));
                }
            } else {
                $this->toLog("ErrorInvalidPasswordAdd", array($name));
            }
        } else {
            $this->toLog("ErrorInvalidNameFormat", array($name));
        }
        return false;
    }

    public function addNewUser($name, $fullName, $password, $role) {
        if ($this->goodToGo) {
            if (empty($this->roleList)) {
                $this->loadRoles();
            }
            $this->execCmd("config");
            $this->execCmd("user " . $name);
            $data = $this->getSshConsoleTxt();
            if (strpos($data, 'created') !== false) {
                return $this->addUser(false, $name, $fullName, $password, $role);
            } else {
                if (strpos($data, 'loaded') !== false) {
                    $this->execCmd('abort' . PHP_EOL . 'abort');
                    $this->toLog("ErrorInvalidNameInUse", array($name));
                    return false;
                } else {
                    $this->execCmd('abort');
                    $this->toLog("ErrorInvalidNameFormat", array($name));
                    return false;
                }
            }
        } else {
            return false;
        }
    }

    public function addUsers($data, $dataType = 'json', $delimiterChar = ';') {
        if ($this->goodToGo) {
            if (empty($this->roleList)) {
                $this->loadRoles();
            }
            $cmds = '';
            if ($dataType === 'txt') {
                $lines = explode("\n", $data);
                $x = 0;
                foreach ($lines as $line) {
                    $x++;
                    $inf = explode($delimiterChar, $line);
                    if (count($inf) == 4) {
						$userName = $inf[0];
						$fullName = $inf[1];
						$role = $inf[2];
						$password = $inf[3];
                        $generatedCmd = $this->addUser(true, $userName, $fullName, $password, $role);
                        if ($generatedCmd) {
                            $cmds .= $generatedCmd;
                        }
                    } else {
                        if (!empty($inf)) {
                            $this->toLog("ErrorIncompletData", array($x));
                        }
                    }
                }
            }
            if ($dataType === 'xml') {
                try {
                    $x = 0;
                    $xml = simplexml_load_string($data);
                    if (!$xml) {
                        $this->toLog("ErrorXmlInvalidFormat");
                        return false;
                    } else {
                        foreach ($xml->children() as $user) {
                            $x++;
                            if ($user->getName() === 'user') {
                                if ($user->children()->count() == 4) {
                                    if ((count($user->userName) > 0) &&
                                        (count($user->fullName) > 0) &&
                                        (count($user->password) > 0) &&
                                        (count($user->role) > 0)
                                    ) {
                                        $uname = $user->userName;
                                        $fname = $user->fullName;
                                        $role = $user->role;
                                        $pass = $user->password;
                                        $generatedCmd = $this->addUser(true, $uname, $fname, $pass, $role);
                                        if ($generatedCmd) {
                                            $cmds .= $generatedCmd;
                                        }
                                    } else {
                                        $this->toLog("ErrorElementsNotFound", array($x));
                                    }
                                } else {
                                    $this->toLog("ErrorIncompletData", array($x));
                                }
                            } else {
                                $this->toLog("ErrorUserElemNotFound", array($x));
                            }
                        }
                    }
                } catch (Exception $e) {
                    $this->toLog($e->getMessage());
                    return false;
                }
            }
            if ($dataType === 'json') {
                $jsData = json_decode($data);
                if ($jsData !== null) {
                    if (count($jsData) > 0) {
                        $x = 0;
                        foreach ($jsData as $jsNode) {
                            $x++;
                            if (count(get_object_vars($jsNode)) == 4) {
                                if (array_key_exists('userName', $jsNode) &&
                                    array_key_exists('fullName', $jsNode) &&
                                    array_key_exists('password', $jsNode) &&
                                    array_key_exists('role', $jsNode)
                                ) {
                                    $uname = $jsNode->userName;
                                    $fname = $jsNode->fullName;
                                    $pass = $jsNode->password;
                                    $role = $jsNode->role;
                                    $generatedCmd = $this->addUser(true, $uname, $fname, $pass, $role);
                                    if ($generatedCmd) {
                                        $cmds .= $generatedCmd;
                                    }
                                } else {
                                   $this->toLog("ErrorJsonKeysNotFound", array($x));
                                }
                            } else {
                                $this->toLog("ErrorIncompletData", array($x));
                            }
                        }
                    } else {
                        $this->toLog("ErrorJsonNoData");
                        return false;
                    }
                } else {
                    $this->toLog("ErrorJsonInvalidFormat");
                    return false;
                }
            }
            $console = $this->execCmdBlock($cmds);
            $this->generateAddLog($console);
            $created = substr_count($console, 'has been created');
            $updated = substr_count($console, 'has been loaded');
            return array($created , $updated);
        } else {
            return false;
        }
    }
    
//------------------- UPDATE -----------------------\\

    private function updateUser($userName, $newName = null, $fullName = null, $password = null, $role = null, $updateFullName = false) {
        $abort = false;
        if (empty($this->roleList) && !empty($role)) {
            $this->loadRoles();
        }                
        $this->execCmd("config");
        $this->execCmd("user " . $userName);
        $data = $this->getSshConsoleTxt();
        if (strpos($data, 'loaded') !== false) {
            $fName = "";
            $pass = "";
            $rol = "";
            $this->execCmd("show");
            $data = $this->getSshConsoleTxt();
            $lines = explode("\n", $data);
            $arr_length = count($lines);
            $i = 0;
            while ($i < $arr_length) {
                if (strpos($lines[$i], 'User Name') !== false) {
                    $fName = substr($lines[$i + 1], 11);
                    $pass = substr($lines[$i + 2], 10);
                    $rol = substr($lines[$i + 3], 6);
                    break;
                } else {
                    $i++;
                }
            }
            if (!empty($newName)) {
                if (preg_match(UserNamePattern, $newName)) {
                    $this->execCmd("user-name " . $newName);
                } else {
                    $this->toLog("ErrorInvalidNewNameFormat", array($userName, $newName));
                    $abort = true;
                }
            }
            if ($updateFullName) {
                $fullName = $this->removeAccents($fullName);
				if (preg_match(FullNamePattern, $fullName)) {
                    $this->execCmd("full-name '" . $fullName . "'");
                    $this->toLog("LogFullNameUpdated", array($userName, $fName, $fullName));
                } else {
                    $this->toLog("ErrorInvalidFullNameUpt", array($userName));
                    $abort = true;
                }
            }
            if (!empty($password)) {
                if (preg_match(PasswordPattern, $password)) {
                    $this->execCmd("password " . $password);
                    $this->toLog("LogPasswordUpdated", array($userName, $pass, $password));
                } else {
                    $this->toLog("ErrorInvalidPasswordUpt", array($userName));
                    $abort = true;
                }
            }
            if (!empty($role)) {
                if (array_search($role, $this->roleList) !== false) {
                    $this->execCmd("role " . $role);
                    $this->toLog("LogUserRoleUpdated", array($userName, $rol, $role));
                } else {
                    $this->toLog("ErrorInvalidRoleUpt", array($userName, $role));
                    $abort = true;
                }
            }
            if ($abort) {
                $this->execCmd("abort" . PHP_EOL . "abort");
				$this->toLog("LogUserNotUpdated", array($userName));
                return false;
            } else {
                $this->execCmd("exit");
                $data = $this->getSshConsoleTxt();
                if ((strpos($data, 'diferente') !== false) ||
                        (strpos($data, 'different') !== false)
                ) {
                    $this->toLog("ErrorInvalidNewNameInUse", array($userName, $newName));
                    $this->execCmd("abort" . PHP_EOL . "abort");
                    return false;
                } else {
                    if (!empty($newName)){
                        $this->toLog("LogUserNameUpdated", array($userName, $newName));
                    }
                    $this->execCmd("exit");
                    return true;
                }
            }
        } else {
            $this->execCmd('abort');
            if (strpos($data, 'created') !== false) {
                $this->execCmd('abort');
            }
            $this->toLog("ErrorUserNotFoundUpt", array($userName));
            return false;
        }
    }

    public function updateUserData($userName, $newName, $newFullName, $newPassword, $newRole) {
        if ($this->goodToGo) {
            return $this->updateUser($userName, $newName, $newFullName, $newPassword, $newRole, true);
        } else {
            return false;
        }
    }

    public function updateUserName($userName, $newName) {
        if ($this->goodToGo) {
            if (empty($newName)) {
                $this->toLog("ErrorInvalidNewUserName", array($userName, " "));
                return false;
            } else {
                return $this->updateUser($userName, $newName);
            }
        } else {
            return false;
        }
    }

    public function updateUserFullName($userName, $newFullName) {
        if ($this->goodToGo) {
            return $this->updateUser($userName, null, $newFullName, null, null, true);
        } else {
            return false;
        }
    }

    public function updateUserPassword($userName, $newPassword) {
        if ($this->goodToGo) {
            if (empty($newPassword)) {
                $this->toLog("ErrorInvalidPasswordUpt", array($userName, " "));
                return false;
            } else {
                return $this->updateUser($userName, null, null, $newPassword);
            }
        } else {
            return false;
        }
    }

    public function updateUserRole($userName, $newRole) {
        if ($this->goodToGo) {
            if (empty($newRole)) {
                $this->toLog("ErrorInvalidRole", array($userName, " "));
                return false;
            } else {
                return $this->updateUser($userName, null, null, null, $newRole);
            }
        } else {
            return false;
        }
    }

    //------------------- DELETE -----------------------\\
	
    public function deleteUsers($data, $dataType = 'json', $delimiterChar = ';') {
        if ($this->goodToGo) {
            $this->loadUsers();
            $userList = array();
            foreach ($this->userList as $user) {
                $userList[] = $user->userName;
            }
            $cmds = '';
            if ($dataType === 'txt') {
                $lines = explode("\n", $data);
                foreach ($lines as $line) {
                    $inf = explode($delimiterChar, $line);
                    if (count($inf) > 0) {
                        if (array_search($inf[0], $userList) !== false) {
                            $cmds .= 'no user ' . $inf[0] . PHP_EOL;
                            $this->toLog("LogUserDeleted", array($inf[0]));
                        }
                        else {
                            $this->toLog("ErrorUserNotFoundDel", array($inf[0]));
                        }
                    }
                }
            }
            if ($dataType === 'xml') {
                try {
                    $xml = simplexml_load_string($data);
                    if (!$xml) {
                        $this->toLog("ErrorXmlInvalidFormat");
                        return false;
                    } else {
                        $x = 0;
                        foreach ($xml->children() as $user) {
                            $x++;
                            if ($user->getName() === 'user') {
                                if ($user->children()->count() > 0) {
                                    if ($user->children()[0]->getName() === 'userName') {
                                        if (array_search($user->children()[0], $userList) !== false) {
                                            $cmds .= 'no user ' . $user->children()[0] . PHP_EOL;
                                            $this->toLog("LogUserDeleted", array($user->children()[0]));
                                        }
                                        else {
                                            $this->toLog("ErrorUserNotFoundDel", array($user->children()[0]));
                                        }
                                    } else {
                                        $this->toLog("ErrorElementsNotFound", array($x));
                                    }
                                } else {
                                    $this->toLog("ErrorIncompletData", array($x));
                                }
                            } else {
                                $this->toLog("ErrorUserElemNotFound", array($x));
                            }
                        }
                    }
                } catch (Exception $e) {
                    $this->toLog($e->getMessage());
                    return false;
                }
            }
            if ($dataType === 'json') {
                $jsData = json_decode($data);
                if ($jsData !== null) {
                    if (count($jsData) > 0) {
                        $x = 0;
                        foreach ($jsData as $jsNode) {
                            $x++;
                            if (array_key_exists('userName', $jsNode)) {
                                if (array_search($jsNode->userName, $userList) !== false) {
                                    $cmds .= 'no user ' . $jsNode->userName . PHP_EOL;
                                    $this->toLog("LogUserDeleted", array($jsNode->userName));
                                }
                                else {
                                    $this->toLog("ErrorUserNotFoundDel", array($jsNode->userName));
                                }
                            } else {
                               $this->toLog("ErrorJsonKeysNotFound", array($x));
                            }
                        }
                    } else {
                        $this->toLog("ErrorJsonNoData");
                        return false;
                    }
                } else {
                    $this->toLog("ErrorJsonInvalidFormat");
                    return false;
                }
            }

            $console = $this->execCmdBlock($cmds);
            return substr_count($console, 'has been deleted');
        } else {
            return false;
        }
    }

    public function deleteUserByName($userName) {
        if ($this->goodToGo) {
            $data = $this->execCmdBlock('no user ' . $userName);
            if (strpos($data, 'has been deleted') !== false) {
                $this->toLog("LogUserDeleted", array($userName));
                return true;
            } else {
                $this->toLog("ErrorUserNotFoundDel", array($userName));
                return false;
            }
        } else {
            return false;
        }
    }

    public function deleteUsersByFullName($fName) {
        if ($this->goodToGo) {
            $this->loadUsers();
            $cmds = '';
            foreach ($this->userList as $user) {
                if (preg_match('/.*' . $fName . '.*/i', $user->fullName)) {
                    $cmds .= 'no user ' . $user->userName . PHP_EOL;
                    $this->toLog("LogUserDeleted", array($user->userName));
                }
            }
            $console = $this->execCmdBlock($cmds);
            return substr_count($console, 'has been deleted');
        } else {
            return false;
        }
    }

    public function deleteAllUsersFromRole($roleName) {
        if ($this->goodToGo) {
            if (empty($this->roleList)) {
                $this->loadRoles();
            }
            if (array_search($roleName, $this->roleList) !== false) {
                $this->loadUsers();
                $cmds = '';
                foreach ($this->userList as $user) {
                    if ($user->role === $roleName) {
                        $cmds .= 'no user ' . $user->userName . PHP_EOL;
                        $this->toLog("LogUserDeleted", array($user->userName));
                    }
                }
                $console = $this->execCmdBlock($cmds);
                return substr_count($console, 'has been deleted');
            } else {
                $this->toLog("ErrorRoleNotFound", array($roleName));
                return false;
            }
        } else {
            return false;
        }
    }

    public function deleteAllUsers() {
        if ($this->goodToGo) {
            $this->loadUsers();
            $cmds = '';
            foreach ($this->userList as $user) {
                $cmds .= 'no user ' . $user->userName . PHP_EOL;
                $this->toLog("LogUserDeleted", array($user->userName));
            }
            
            $console = $this->execCmdBlock($cmds);
            return substr_count($console, 'has been deleted');
        } else {
            return false;
        }
    }
    
    //------------------- BACKUP -----------------------\\

    public function createBackup($role = '', $returnType = 'json', $delimiterChar = ';') {
        if ($this->goodToGo) {
            $this->loadUsers();
            $userList = array();
            if ($role !== '') {
                foreach ($this->userList as $user) {
                    if ($user->role === $role) {
                        $userList[] = $user;
                    }
                }
            } else {
                $userList = $this->userList;
            }

            $data = "";
            $text = '';
            if ($returnType === 'xml') {
                $data = $this->convertUserData($userList, $returnType, $delimiterChar, true);
                $dom = new DOMDocument('1.0');
                $dom->preserveWhiteSpace = false;
                $dom->formatOutput = true;
                $dom->loadXML($data);
                $text = html_entity_decode($dom->saveXML());
            } else {
                if ($returnType === 'json') {
                    foreach ($userList as $user) {
                        unset($user->id);
                    }
                    $text = json_encode($userList, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                } else {
                    $text = $this->convertUserData($userList, $returnType, $delimiterChar, true);
                }
            }
            $path = substr('zdbackups/', 0, -1);
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
            $fileName = 'zdbackups/backup_' . date("Y-m-d_H-i-s") . "." . $returnType;
            $myfile = fopen($fileName, "a") or die("Unable to open file!");
            fwrite($myfile, $text);
            fclose($myfile);
            $this->toLog("BackupCreated", array($fileName));
            return $fileName;
        } else {
            return false;
        }
    }

}
