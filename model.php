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

    function fetchAverageData($db) {
        $classId = $_SESSION['classId'];
        $fetchGroupData = "SELECT groupId, names FROM `groups` WHERE classId = :classId";
        $statement = $db->prepare($fetchGroupData);
        $statement->bindvalue(":classId", $classId);
        $statement->execute();
        $groupData = $statement->fetchall();
        $groupIds = [];
        $groupNames = [];
        foreach ($groupData as $group) {
            array_push($groupIds, intval($group['groupId']));
            array_push($groupNames, $group['names']);
        }
        $_SESSION['groupNames'] = $groupNames;
        $_SESSION['groupIds'] = $groupIds;

        $data = [];
        foreach($groupIds as $id) {
            $fetchGroupScoreData = "SELECT Score1, Score2, Score3, AvgScore, Comments FROM `Grades` WHERE groupId = :groupId";
            $statement = $db->prepare($fetchGroupScoreData);
            $statement->bindvalue(":groupId", $id);
            $statement->execute();
            $res = $statement->fetchAll();
            
            if (sizeof($res) != 0) {
                $s1 = $s2 = $s3 = 0;
                $comments = "";
                $i = 0;

                foreach ($res as $result) {                    
                    $s1 += $result['Score1'];
                    $s2 += $result['Score2'];
                    $s3 += $result['Score3'];
                    if (strlen($comments) == 0 && $result['Comments'] != null) {
                        $comments .= $result['Comments'];
                    } else if (strlen($comments) != 0 && $result['Comments'] != null) { 
                        $comments .= ", " . $result['Comments'];
                    }
                    $i += 3;
                }
                $avgS1 = $s1 / ($i/3);
                $avgS2 = $s2 / ($i/3);
                $avgS3 = $s3 / ($i/3);
                $avg = ($s1 + $s2 + $s3) / $i;
                array_push($data, [$avgS1, $avgS2, $avgS3, $avg, $comments]);
            } else {
                array_push($data, false);
            }
            
            
        }
        $_SESSION['data'] = $data;
    }

    function fetchAllGradeData($db) {
        $getStudentIds = "SELECT studentId FROM `Students` WHERE classId = :classId";
        $statement = $db->prepare($getStudentIds);
        $statement->bindvalue(':classId', $_SESSION['classId']);
        $statement->execute();
        $res = $statement->fetchAll();


        $tempStudents = getStudents($db);
        $studentIds = [];
        foreach ($res as $result) {
            array_push($studentIds, $result['studentId']);
        }
        $students = [];
        for ($i = 0; $i < sizeof($tempStudents); $i++) {
            array_push($students, $tempStudents[$i]['studentName']);            
        }
        $_SESSION['Students'] = $students;

        $studentData = [];
        foreach ($studentIds as $id) {
            $fetchStudentData = "SELECT groupId, Score1, Score2, Score3, AvgScore, Comments FROM `Grades` WHERE studentId = :studentId";
            $statement = $db->prepare($fetchStudentData);
            $statement->bindvalue(":studentId", $id);
            $statement->execute();
            $res = $statement->fetchAll();
            if (sizeof($res) == 0) {
                array_push($studentData, false);
            } else {
                foreach ($res as $grade) {
                    $temp = [];
                    $getGroup = "SELECT names FROM `groups` WHERE groupId = :groupId";
                    $statement = $db->prepare($getGroup);
                    $statement->bindvalue(':groupId', $grade['groupId']);
                    $statement->execute();
                    $names = $statement->fetch();

                    $getStudentName = "SELECT studentName FROM `Students` WHERE studentId = :studentId";
                    $statement = $db->prepare($getStudentName);
                    $statement->bindvalue(':studentId', $id);
                    $statement->execute();
                    $studentName = $statement->fetch();

                    array_push($temp, $studentName['studentName']);
                    array_push($temp, $names['names']);
                    array_push($temp, $grade['Score1']);
                    array_push($temp, $grade['Score2']);
                    array_push($temp, $grade['Score3']);
                    array_push($temp, $grade['AvgScore']);
                    array_push($temp, $grade['Comments']);
                    array_push($studentData, $temp);
                }

            }
        }
        $_SESSION['studentData'] = $studentData;
    }

?>