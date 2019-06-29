<?php

include 'ZoneDirectorUserManager.php';
$ruckusMng = new ZoneDirectorUserManager();

function returnError($mng) {
    $jsonResult = array();
    $jsonResult['Result'] = "ERROR";
    $jsonResult['Message'] = $mng->getLastError($_GET['language']);
    print json_encode($jsonResult);
}

function returnUsers($userList, $totalRecordCount = null) {
    $jsonResult = array();
    $jsonResult['Result'] = "OK";
    if ($totalRecordCount !== null) {
        $jsonResult['TotalRecordCount'] = $totalRecordCount;
    }
    $jsonResult['Records'] = $userList;
    print json_encode($jsonResult);
}

function returnData($data, $totalRecordCount = null) {
    $jsonResult = array();
    $jsonResult['Result'] = "OK";
    if ($totalRecordCount !== null) {
        $jsonResult['TotalRecordCount'] = $totalRecordCount;
        $jsonResult['Records'] = $data;
    } else {
        $jsonResult['Records'] = json_decode($data);
    }
    print json_encode($jsonResult);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_GET['action']) && isset($_GET['language'])) {
        $action = $_GET['action'];
        if ($ruckusMng->isGoodToGo()) {
            /*             * **************************** GETS ***************************** */
            if ($action === "loadUsers") {
                $data = null;
                if (empty($_POST)) {
                    $data = $ruckusMng->getUsersData('array');
                } else {
                    if (isset($_POST['filterType']) && isset($_POST['value'])) {
                        if ($_POST['filterType'] === "role") {
                            if (empty($_POST["value"])) {
                                $data = $ruckusMng->getUsersData('array');
                            } else {
                                $data = $ruckusMng->getUsersDataByRole($_POST["value"], 'array');
                            }
                        }
                        if ($_POST['filterType'] === "name") {
                            $data = $ruckusMng->getUsersDataByName($_POST["value"], 'array');
                        }
                        if ($_POST['filterType'] === "fullName") {
                            $data = $ruckusMng->getUsersDataByFullName($_POST["value"], 'array');
                        }
                    } else {
                        $data = $ruckusMng->getUsersData('array');
                    }
                }
                if ($data !== false) {
                    $userList = array_slice($data, $_GET['jtStartIndex'], $_GET['jtPageSize']);
                    returnUsers($userList, Count($data));
                } else {
                    returnError($ruckusMng);
                }
            }
            if ($action === "loadRoles") {
                $data = $ruckusMng->getRoles('array');
                if ($data !== false) {
                    $roleList = array();
                    foreach ($data as $role) {
                        $roleList[] = array("DisplayText" => $role, "Value" => $role);
                    }
                    $jsonResult = array();
                    $jsonResult['Result'] = "OK";
                    $jsonResult['Options'] = $roleList;
                    print json_encode($jsonResult);
                } else {
                    returnError($ruckusMng);
                }
            }
            /*             * **************************** SETS ***************************** */
            if ($action === "addUser") {
                $data = $ruckusMng->addNewUser($_POST["userName"], $_POST["fullName"], $_POST["password"], $_POST["role"]);
                if ($data !== false) {
                    $data = $ruckusMng->getUserDataByName($_POST["userName"], 'array');
                    $jsonResult = array();
                    $jsonResult['Result'] = "OK";
                    $jsonResult['Record'] = $data;
                    print json_encode($jsonResult);
                } else {
                    returnError($ruckusMng);
                }
            }
            if ($action === "addUsers") {
                $totalRecords = $ruckusMng->addUsers($_POST["data"], $_POST["dataType"], $_POST["delimiterChar"]);
                if ($totalRecords !== false) {
                    returnData($ruckusMng->getLog(), $totalRecords);
                } else {
                    returnError($ruckusMng);
                }
            }
            /*             * *************************** UPDATE **************************** */
            if ($action === "updateUser") {
                $data = $ruckusMng->updateUserData($_POST["name"], $_POST["userName"], $_POST["fullName"], $_POST["password"], $_POST["role"]);
                if ($data !== false) {
                    returnData($data);
                } else {
                    returnError($ruckusMng);
                }
            }
            /*             * *************************** EXCLUDE **************************** */

            if ($action === "deleteUsers") {
                $totalRecords = $ruckusMng->deleteUsers($_POST["data"], $_POST["dataType"], $_POST["delimiterChar"]);
                if ($totalRecords !== false) {
                    returnData($ruckusMng->getLog(), $totalRecords);
                } else {
                    returnError($ruckusMng);
                }
            }
            if ($action === "deleteUserByName") {
                $data = $ruckusMng->deleteUserByName($_POST["userName"]);
                if ($data !== false) {
                    returnData($data);
                } else {
                    returnError($ruckusMng);
                }
            }
            if ($action === "deleteAllUsersFromRole") {
                $totalRecords = $ruckusMng->deleteAllUsersFromRole($_POST["role"]);
                if ($totalRecords !== false) {
                    returnData($ruckusMng->getLog(), $totalRecords);
                } else {
                    returnError($ruckusMng);
                }
            }
            if ($action === "deleteAllUsers") {
                $totalRecords = $ruckusMng->deleteAllUsers();
                if ($totalRecords !== false) {
					returnData($ruckusMng->getLog(), $totalRecords);
                } else {
                    returnError($ruckusMng);
                }
            }

            /*             * *************************** BACKUP **************************** */
            if ($action === "backupUsers") {
                $data = $ruckusMng->createBackup($_POST["role"], $_POST["type"], $_POST["delimiterChar"]);
                if ($data !== false) {
                    $jsonResult = array();
                    $jsonResult['Result'] = "OK";
                    $jsonResult['Link'] = $data;
                    print json_encode($jsonResult);
                } else {
                    returnError($ruckusMng);
                }
            }
        } else {
            returnError($ruckusMng);
        }
    }
}