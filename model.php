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

    function createClassRoom($db, $groups, $questions) { //still needs error checking
        $accessCode = random_int(0, 10000);
        $classPass = getRandPass();
    
        //create new classRoom table entry    
        $createClass = "INSERT INTO `Classrooms` (accessCode, classPassword, Question1, Question2, Question3) VALUES (:accessCode, :classPass, :Question1, :Question2, :Question3)";
        $statement = $db->prepare($createClass);
        $statement->bindValue(':accessCode', $accessCode);
        $statement->bindValue(':classPass', $classPass);
        $statement->bindvalue(':Question1', $questions[0]);
        $statement->bindvalue(':Question2', $questions[1]);
        $statement->bindvalue(':Question3', $questions[2]);
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

        $_SESSION['accessCode'] = $accessCode;
        $_SESSION['classId'] = $cId;
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
        $westernIds = [];
        foreach ($studentIds as $id) {
            $fetchStudentData = "SELECT groupId, Score1, Score2, Score3, AvgScore, Comments, westernId FROM `Grades` WHERE studentId = :studentId";
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
                    $westernIds[$studentName['studentName']] = $grade['westernId'];
                    array_push($studentData, $temp);
                }

            }
        }
        $_SESSION['westernIds'] = array_unique($westernIds);
        $_SESSION['studentData'] = $studentData;

        
    }

    function getStudentData($db) {
        $studentName = $_SESSION['studentName'];
        $classId = $_SESSION['classId'];

        $fetchGroups = "SELECT names FROM `groups` WHERE classId = :classId";
        $statement = $db->prepare($fetchGroups);
        $statement->bindvalue('classId', $classId);
        $statement->execute();
        $res = $statement->fetchall();
        $names = [];
        foreach ($res as $group) {
            if (str_contains($group['names'], $_SESSION['studentName'])) { 
            } else {
                array_push($names, $group['names']);
            }
        }
        $_SESSION['names'] = $names;

        $fetchGroupIds = "SELECT groupId FROM `groups` WHERE classId = :classId";
        $statement = $db->prepare($fetchGroupIds);
        $statement->bindvalue(":classId", $classId);
        $statement->execute();
        $res = $statement->fetchall();

        $groupIds = [];
        foreach ($res as $id) {
            array_push($groupIds, $id['groupId']);
        }
        $_SESSION['groupIds'] = $groupIds;
    }

    function storeEval($db, $data) {
        $fetchStudentId = "SELECT studentId FROM `Students` WHERE (studentName = :studentName AND classId = :classId)";
        $statement = $db->prepare($fetchStudentId);
        $statement->bindvalue(':studentName', $_SESSION['studentName']);
        $statement->bindvalue(':classId', $_SESSION['classId']);
        $statement->execute();
        $studentId = $statement->fetch();
        $studentId = intval($studentId['studentId']);
        foreach ($_SESSION["groupIds"] as $id) {
            $s1 = $data['1eval'.$id];
            $s2 = $data['2eval'.$id];
            $s3 = $data['3eval'.$id];
            is_null($s1) ? $s1 = 0;
            is_null($s2) ? $s2 = 0;
            is_null($s3) ? $s3 = 0;
            $avg = floatval(($s1+$s2+$s3)/3);
            $insertEval = "INSERT INTO `Grades` (groupId, Score1, Score2, Score3, AvgScore, Comments, studentId, westernId) VALUES (:groupId, :Score1, :Score2, :Score3, :AvgScore, :Comments, :studentId, :westernId)";
            $statement = $db->prepare($insertEval);
            $statement->bindvalue(':groupId', $id);
            $statement->bindvalue(':Score1', $s1);
            $statement->bindvalue(':Score2', $s2);
            $statement->bindvalue(':Score3', $s3);
            $statement->bindvalue(':AvgScore', $avg);
            $statement->bindvalue(':Comments', $data['comment'.$id]);
            $statement->bindvalue(':studentId', $studentId);
            $statement->bindvalue(':westernId', $_SESSION['westernId']);
            $statement->execute();
        }
    }

    function getQuestions($db, $accessCode) {
        $getQuestion = 'SELECT Question1, Question2, Question3 FROM `Classrooms` WHERE accessCode = :accessCode';
        $statement = $db->prepare($getQuestion);
        $statement->bindvalue(':accessCode', $accessCode);
        $statement->execute();
        $qs = $statement->fetch();

        return $qs;
    }

?>
