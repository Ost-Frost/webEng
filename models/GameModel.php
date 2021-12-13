<?php

    require("ModelBasis.php");

    /**
     * model for the register page
     */
    class GameModel extends ModelBasis {

        private $gameStructure = [];

        public function getGameStructure() {
            $userID = $_SESSION['userID'];
            $sqlQueryGame = "SELECT SpielID FROM `spielkarte` WHERE UserID='$userID'";
            $sqlQueryEpic = "SELECT EpicID FROM `epicuser` WHERE UserID='$userID'";
            $this->dbConnect();
            $resultGame = $this->dbSQLQuery($sqlQueryGame);
            $resultEpic = $this->dbSQLQuery($sqlQueryEpic);
            $allEpic = [];

            while($row=$resultEpic->fetch_assoc()) {
                $epicIDTemp = $row["EpicID"];
                $sqlQueryEpic = "SELECT EpicID, Name, Beschreibung, Aufwand, Einrichtungsdatum FROM `epic` WHERE EpicID='$epicIDTemp'";
                $epicgames = $this->dbSQLQuery($sqlQueryEpic);
                if($allEpicGames=$epicgames->fetch_assoc()) {
                    $epic = [];
                    $epic["Name"] = $allEpicGames["Name"];
                    $epic["Beschreibung"] = $allEpicGames["Beschreibung"];
                    $epic["Aufwand"] = $allEpicGames["Aufwand"];
                    $epic["Einrichtungsdatum"] = $allEpicGames["Einrichtungsdatum"];
                    $epic["EpicID"] = $allEpicGames["EpicID"];
                    $epic["games"] = [];
                    array_push($allEpic, $epic);
                }
            }
            $games = [];
            while($row=$resultGame->fetch_assoc()) {
                $gameIDTemp = $row["SpielID"];
                $sqlQueryGame = "SELECT Task, Beschreibung, Aufwand, Einrichtungsdatum, SpielID FROM `spiele` WHERE SpielID='$gameIDTemp'";
                $sqlQueryUser = "SELECT UserID, Karte, UserStatus FROM `spielkarte` WHERE SpielID='$gameIDTemp'";
                $resultUsers = $this->dbSQLQuery($sqlQueryUser);
                $resultGameInfo = $this->dbSQLQuery($sqlQueryGame);
                $usersList = [];
                $games = [];
                while($users=$resultUsers->fetch_assoc()) {
                    $userInfos = [];
                    $ID = $users["UserID"];
                    $sqlQueryUserInfo = "SELECT Username, UserID FROM `user` WHERE UserID='$ID'";
                    $resultUserInfo = $this->dbSQLQuery($sqlQueryUserInfo);
                    if($uInfo=$resultUserInfo->fetch_assoc()) {
                        $userInfos["Username"] = $uInfo["Username"];
                        $userInfos["Karte"] = $users["Karte"];
                        $userInfos["Userstatus"] = $users["UserStatus"];
                        $userInfos["UserID"] = $uInfo["UserID"];
                        array_push($usersList, $userInfos);
                    }
                }
                if($gameInfo = $resultGameInfo->fetch_assoc()) {
                    $game = [];
                    $game["Task"] = $gameInfo["Task"];
                    $game["Beschreibung"] = $gameInfo["Beschreibung"];
                    $game["Aufwand"] = $gameInfo["Aufwand"];
                    $game["Einrichtungsdatum"] = $gameInfo["Einrichtungsdatum"];
                    $game["SpielID"] = $gameInfo["SpielID"];
                    $game["user"] = $usersList;
                    array_push($games, $game);
                }
            }
            $gamesWOEpic = [];
            foreach($games as $ga) {
                $gameID = $ga["SpielID"];
                $sqlQueryWOE = "SELECT EpicID FROM `epicspiel` WHERE SpielID='$gameID'";
                $gamesWEO = $this->dbSQLQuery($sqlQueryWOE);
                if($epiC = $gamesWEO->fetch_assoc()) {
                    foreach($allEpic as $index => $singleEpic) {
                        if($allEpic[$index]["EpicID"] == $epiC["EpicID"]) {
                            array_push($allEpic[$index]["games"], $ga);
                        }
                    }
                } else {
                    array_push($gamesWOEpic, $ga);
                }
            }
            $gamesWOEpic = [];
            while($gwoe = $resultGame->fetch_assoc()) {
                $spielIDwoe = $gwoe["SpielID"];
                $sqlQuerywoe = "SELECT EpicID FROM `epicspiel` WHERE SpielID='$spielIDwoe'";
                $gamesWE = $this->dbSQLQuery($sqlQuerywoe);
                if($games = $gamesWE->fetch_assoc()) {
                    continue;
                } else {
                    $gWOE = [];
                    $gWOE["Task"] = $gwoe["Task"];
                    $gWOE["Beschreibung"] = $gwoe["Beschreibung"];
                    $gWOE["Aufwand"] = $gwoe["Aufwand"];
                    $gWOE["Einrichtungsdatum"] = $gwoe["Einrichtungsdatum"];
                    $gWOE["SpielID"] = $gwoe["SpielID"];
                    array_push($gamesWOEpic, $gWOE);
                }
            }

            $this->gameStructure["gamesWOEpic"] = $gamesWOEpic;
            $this->gameStructure["allEpic"] = $allEpic;
            $this->gameStructure = $this->gameStructure;
            return $this->gameStructure;
        }

        /**
         * gets data for the search API and returns it as an array by searching for every userName or eMail that starts with the requested string
         *
         * @return mixed if at least one user was found the method returns an array with all users, otherwise it returns false
         */
        public function searchUser() : mixed {

            $userName = $_GET["userName"];
            $userName = str_replace("%", "\%", $userName);
            $userName = str_replace("_", "\_", $userName);
            $response = [];
            $sqlQuery = "SELECT Username, UserID FROM user WHERE (Username LIKE '$userName%' OR Mail LIKE '$userName%') ORDER BY Username ASC";
            $this->dbConnect();
            $result = $this->dbSQLQuery($sqlQuery);
            while ($row = mysqli_fetch_assoc($result)) {
                $foundUserName = $row["Username"];
                if ($row["UserID"] === $_SESSION["userID"]) {
                    continue;
                }
                array_push($response, $foundUserName);
            }
            $this->dbClose();
            if (count($response) === 0) {
                return false;
            }
            return $response;
        }

        /**
         * gets data for the search API and returns it as an array by searching for every epicName that starts with the requested string
         *
         * @return mixed if at least one user was found the method returns an array with all epics, otherwise it returns false
         */
        public function searchEpic() : mixed {
            $epicName = $_GET["epicName"];
            $epicName = str_replace("%", "\%", $epicName);
            $epicName = str_replace("_", "\_", $epicName);
            $userID = $_SESSION["userID"];
            $response = [];
            $sqlQueryEpicID = "SELECT e.EpicID FROM epicspiel e INNER JOIN spiele s ON e.SpielID = s.SpielID INNER JOIN spielkarte sk ON s.SpielID = sk.SpielID WHERE sk.UserID='$userID'";
            $this->dbConnect();
            $resultID = $this->dbSQLQuery($sqlQueryEpicID);
            while ($row = mysqli_fetch_assoc($resultID)) {
                $epicID = $row["EpicID"];
                $sqlQueryEpics = "SELECT Name FROM epic WHERE (Name LIKE '$epicName%') AND EpicID='$epicID' ORDER BY Name ASC";
                $resultEpic = $this->dbSQLQuery($sqlQueryEpics);
                if($epics=$resultEpic->fetch_assoc()) {
                    $alreadyFound = false;
                    foreach ($response as $foundEpicName) {
                        if ($foundEpicName === $epics["Name"]) {
                            $alreadyFound = true;
                        }
                    }
                    if (!$alreadyFound) {
                        array_push($response, $epics["Name"]);
                    }
                } else {
                    continue;
                }
            }
            $this->dbClose();
            if (count($response) === 0) {
                return [];
            }
            return $response;
        }

        /**
         * checks array for double values
         *
         * @return array with all doubled values removed
         */
        private function checkDouble($array) : array {
            $addV = true;
            $uniqueArray = [];
            foreach($array as $field => $value) {
                foreach($uniqueArray as $doubleField => $doubleValue) {
                    if($value == $doubleValue) {
                        $addV = false;
                        break;
                    }
                }
                if($addV) {
                    array_push($uniqueArray, $value);
                }
            }
            return $uniqueArray;
        }

        /**
         * deletes the game with the requested gameID
         *
         * @return mixed SQL query response
         */
        public function deleteGame() : mixed {
            $gameID = $_POST["gameid"];

            $sqlQuery = "DELETE FROM `spiele` WHERE `spiele`.`SpielID` = $gameID";

            $this->dbConnect();
            $response = $this->dbSQLQuery($sqlQuery);
            $this->dbClose();

            return $response;
        }

        /**
         * gets data for the search API and returns it as an array by searching for every userName or eMail that starts with the requested string
         *
         * @return mixed if at least one user was found the method returns an array with all users, otherwise it returns false
         */
        public function playCard() {
            $value = $_REQUEST["value"];
            $gameID = $_REQUEST["gameID"];
            $userID = $_SESSION['userID'];

            $sqlQuery = "UPDATE `spielkarte` SET Karte='$value' Akzeptiert='2' WHERE UserID='$userID' AND SpielID='$gameID'";
            $this->dbConnect();
            $result = $this->dbSQLQuery($sqlQuery);
            $this->dbClose();

            if($row = $response->fetch_assoc()) {
                if($row == 0) {
                    return false;
                }
                return true;
            }
            return false;
        }
    }

?>