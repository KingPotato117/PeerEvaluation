<?php
    function getRandPass(): String {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $pieces = [];
        $max = mb_strlen($characters, '8bit') - 1;
        for ($i = 0; $i < 16; ++$i) {
            $pieces []= $characters[random_int(0, $max)];
    }
    return implode('', $pieces);
    }

    function createClassRoom($db, $groups) { //still needs error checking
        try {
        $accessCode = random_int(0, 10000);
        $classPass = getRandPass();
    
        //create new classRoom table entry    
        $createClass = "INSERT INTO `Classrooms` (accessCode, classPassword) VALUES (:accessCode, :classPass)";
        $statement = $db->prepare($createClass);
        $statement->bindValue(':accessCode', $accessCode);
        $statement->bindValue(':classPass', $classPass);
        $success = $statement->execute();
        var_dump($success);

        $getClassId = "SELECT classId FROM `Classrooms` WHERE accessCode = :accessCode AND classPassword = :classPass";
        $statement = $db->prepare($getClassId);
        $statement->bindValue(':accessCode', $accessCode);
        $statement->bindValue(':classPass', $classPass);
        $statement->execute();
        $classId = $statement->fetch();
        $cId = $classId['classId'];

        $insertStudent = "INSERT INTO `Students` (studentName, classId) VALUES (:studentName, :classId)";

        //parse groups 
        foreach ($groups as $group){
                //go through each name in group, format string properly for group and insert into students table  
                $names = "";
                foreach ($group as $name) {
                    $name = ltrim($name, "\n ");
                    if ($name == end($group)) {
                        $names .= $name;
                    } else {
                        $names .= $name . ", ";
                    }
                    $statement = $db->prepare($insertStudent);
                    $statement->bindValue(':studentName', $name);
                    $statement->bindValue(':classId', $cId);
                    $statement->execute();
                }
                //trim whitespace and newlines
                $names = ltrim($names, "\n ");

                $insertGroups = "INSERT INTO `groups` (names, classId) VALUES (:names, :classId)";
                $statement = $db->prepare($insertGroups);
                $statement->bindvalue(':names', $names);
                $statement->bindvalue(':classId', $cId);
                $statement->execute();
            }
        } catch(PDOExecption $e) {}
    }

    function joinClassRoom($db, $accessCode) {
        $checkClassId = "SELECT classId FROM `Classrooms` WHERE accessCode = :accessCode";
        $statement = $db->prepare($checkClassId);
        $statement->bindValue(':accessCode', $accessCode);
        $statement->execute();
        $res = $statement->fetch();
        return $res;
    }

    function getStudents($db) {
        $getNames = "SELECT studentName FROM `Students` WHERE classId = :classId";
        $statement = $db->prepare($getNames);
        $statement->bindvalue(':classId', $_SESSION['classId']);
        $statement->execute();
        $res = $statement->fetchAll();
        return $res;
    }

    function checkPassword($db, $accessCode) {
        $checkPass = "SELECT classPassword FROM `Classrooms` WHERE accessCode = :accessCode";
        $statement = $db->prepare($checkPass);
        $statement->bindvalue(':accessCode', $accessCode);
        $statement->execute();
        $res = $statement->fetch();
        return $res;
    }

    function fetchTeacherData($db) {
        $classId = $_SESSION['classId'];

        fetchGroupData = "SELECT groupId, names WHERE "
        fetchGroupScoreData = "SELECT Score1, Score2, Score3, AvgScore, Comments, StudentId WHERE class"
    }


?>