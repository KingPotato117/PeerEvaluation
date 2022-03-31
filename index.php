<?php

    require("model.php");
         
    session_set_cookie_params(4000, '/');          
    session_start();

    $dsn = 'mysql:host=localhost;dbname=PeerEval';
    $dbusername = 'prototype';
    $dbpassword = 'CS495';
    include("navBar.html");
     //Get Heroku ClearDB connection information
    $cleardb_url = parse_url(getenv("CLEARDB_DATABASE_URL"));
    $cleardb_server = $cleardb_url["host"];
    $cleardb_username = $cleardb_url["user"];
    $cleardb_password = $cleardb_url["pass"];
    $cleardb_db = substr($cleardb_url["path"],1);
    $active_group = 'default';
    $query_builder = TRUE;
// Connect to DB
    $conn = mysqli_connect($cleardb_server, $cleardb_username, $cleardb_password, $cleardb_db);
    
    
    try {
        $db = new PDO($dsn, $dbusername, $dbpassword);
        //handle GET requests
        if (isset($_GET["createClass"])) {  //display createClassroom page 
            include("createClass.html");
        } else if(isset($_GET["joinClass"])) { //display joinClassroom page
            include("joinClass.html");
        } else if (isset($_GET['student'])) { //display student login page 
            include("joinClassStudent.html");
            $students = getStudents($db);
            echo "<form method='POST'>";
            echo "<label for='students' >Please select your name: </label>";
            echo "<select name='students' required>";
            //future: put in html
            foreach ($students as $student) {
                echo "<option value='". $student["studentName"] . "' >". $student["studentName"] .  "</option>";
            }
            echo "<input type='submit' name='studentLogin' />";
            echo "</select>";
            echo "</form>";
        } else if (isset($_GET['teacher'])) { //display teacher login page 
            include("joinClassTeacher.html");
        } else if (isset($_GET['teacherView'])) { //display teacher user page 
            fetchAverageData($db);
            fetchAllGradeData($db);
            include("teacherView.html");
        } else if (isset($_GET['studentView'])) { //display student login user page
            getStudentData($db);
            $id = 0;
            echo "<form method='POST'>";
            foreach ($_SESSION['names'] as $names) {
                echo "<h3>Group: $names </h3><br>";
                echo "<label for='1eval{$_SESSION['groupIds'][$id]}'>Originality and Aesthetics - clearly shows asymmetric equilibrium:\t\t</label>";
                echo "<input name='1eval{$_SESSION['groupIds'][$id]}' type='number' min=1 max=5 required><br>";
                echo "<label for='2eval{$_SESSION['groupIds'][$id]}'>Display has all equations of equilibrium calculated and neatly shown:\t\t</label>";
                echo "<input name='2eval{$_SESSION['groupIds'][$id]}' type='number' min=1 max=5 required><br>";
                echo "<label for='3eval{$_SESSION['groupIds'][$id]}'>Quality of project design and implementation:\t\t</label>";
                echo "<input name='3eval{$_SESSION['groupIds'][$id]}' type='number' min=1 max=5 required><br>";
                echo "<label for='comment{$_SESSION['groupIds'][$id]}'>Comments:\t\t</label>";
                echo "<input name='comment{$_SESSION['groupIds'][$id]}' type='text'><br>";
                echo "<input name='eval' type='hidden'>";
                echo "<br>";
                $id++;
            }
            echo "<input type='submit'>";
            echo "</form>";
           
        } else if (isset($_GET['complete'])) { //display complete page 
            include("complete.html"); 
        } else {                            //display main page 
            include("home.html");
        }

        //handle POST requests 
        if(isset($_POST['submitFile'])) { //handle file upload m
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
            header("Refresh: 0; url=teacherView"); 
            }
        } else if (isset($_POST['userType'])) { //hander user type form
            $classId = joinClassRoom($db, $_POST['accessCode']);
            if ($classId != false) {
                $classId = $classId['classId'];
                $_SESSION['classId'] = $classId;
                $_SESSION['accessCode'] = $_POST['accessCode'];
                if ($_POST['userType'] == 'Student') {
                    header("Refresh: 0; student");
                } else if ($_POST['userType'] == 'Teacher') {
                    header("Refresh: 0; url=teacher");
                } 
            } else {
                echo "<p>Class with code " . $_POST['accessCode'] . " does not exist! Check code and try again.</p>";
            }
        } else if (isset($_POST['teacherLogin'])) { //handle teacher login form
            $givenPass = $_POST['password'];
            $result = checkPassword($db, $_SESSION['accessCode']);
            $pass = $result['classPassword'];
            if ($givenPass == $pass) {
                header("Refresh: 0; url=teacherView");
            } else {
                echo "<p>Password given does not match password on record, please try again. </p>";
            }
        } else if (isset($_POST['studentLogin'])) { //handle student login form  
            $_SESSION['studentName'] = $_POST['students'];
            header("Refresh: 0; url=studentView");
        } else if (isset($_POST['eval'])) {  //handle student completing eval 
            storeEval($db, $_POST);
            header("Refresh: 0; url=complete"); //not working
        }
    } catch (PDOException $e) {
        var_dump($e);
    }

    include("footer.html");
?>
