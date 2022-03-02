<?php
    require("model.php");
         
    session_set_cookie_params(4000, '/');          
    session_start();

    $dsn = 'mysql:host=localhost;dbname=PeerEval';
    $dbusername = 'prototype';
    $dbpassword = 'CS495';

    include("navBar.html");

    try {
        $db = new PDO($dsn, $dbusername, $dbpassword);
        //handle GET requests
        if (isset($_GET["createClass"])) {  //display createClassroom page 
            include("createClass.html");
        } else if(isset($_GET["joinClass"])) { //display joinClassroom page
            include("joinClass.html");
        } else if (isset($_GET["read"])) {  //display techer read page 
            include("read.html");
        } else if (isset($_GET['student'])) {
            include("joinClassStudent.html");
            $students = getStudents($db);
            echo "<form method='POST'>";
            echo "<label for='students' >Please select your name: </label>";
            echo "<select name='students' >";

            foreach ($students as $student) {
                echo "<option value='". $student["studentName"] . "' >". $student["studentName"] .  "</option>";
            }

            echo "<input type='submit' name='studentLogin' />";
            echo "</select>";
            echo "</form>";
        }else if (isset($_GET['teacher'])) {
            include("joinClassTeacher.html");
        } else if ($_GET['teacherView']) {
            fetchTeacherData($db);
            include("teacherView.html");
        } else {                            //display main page 
            include("home.html");
        }

        //handle POST requests 
        if(isset($_POST['submitFile'])) {
            if ($_FILES['studentGroups']['error'] == UPLOAD_ERR_OK        
                  && is_uploaded_file($_FILES['studentGroups']['tmp_name'])) {
                $contents = file_get_contents($_FILES['studentGroups']['tmp_name']);
                $groups = explode(".", $contents);
                foreach ($groups as &$group) {
                    if ($group == "") {
                        $blank = array_search("", $groups);
                        unset($groups[$blank]);
                    } else {
                        $group = explode(", ", $group);
                    }
                }
            createClassroom($db, $groups);
            header("Refresh: 0; url=index.php?read");
            }
        } else if (isset($_POST['userType'])) {
            $classId = joinClassRoom($db, $_POST['accessCode']);
            if ($classId != false) {
                $classId = $classId['classId'];
                $_SESSION['classId'] = $classId;
                $_SESSION['accessCode'] = $_POST['accessCode'];
                if ($_POST['userType'] == 'Student') {
                    header("Refresh: 0; url=index.php?student");
                } else if ($_POST['userType'] == 'Teacher') {
                    header("Refresh: 0; url=index.php?teacher");
                } 
            } else {
                echo "<p>Class with code " . $_POST['accessCode'] . " does not exist! Check code and try again.</p>";
            }
        } else if (isset($_POST['teacherLogin'])) {
            $givenPass = $_POST['password'];
            $result = checkPassword($db, $_SESSION['accessCode']);
            $pass = $result['classPassword'];
            if ($givenPass == $pass) {
                $_SESSION['teacherLoggedIn'] = true;
                header("Refresh: 0; url=index.php?teacherView");
            } else {
                echo "<p>Password given does not match password on record, please try again. </p>";
            }
        }
    } catch (PDOException $e) {

    }

    include("footer.html");
?>