<?php
    require("model.php");
         
    session_set_cookie_params(4000, '/');          
    session_start();

    $dsn = 'mysql:host=us-cdbr-east-05.cleardb.net/;dbname=heroku_a500c3e55d9d3ba';
    $dbusername = 'bc63908529504f';
    $dbpassword = '6cf43e72';


    include("navBar.html");

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
            echo "<label for='westernId'>Western Student Id: </label>";
            echo "<input type='number' name='westernId'/>";
            echo "<br/>";
            echo "<label for='students' >Please select your name: </label>";
            echo "<select name='students' required>";
            //future: put in html 
            foreach ($students as $student) {
                echo "<option value='". $student["studentName"] . "' >". $student["studentName"] .  "</option>";
            }
            echo "<br/>";
            echo "<input type='submit' name='studentLogin' />";
            echo "</select>";
            echo "</form>";
        } else if (isset($_GET['teacher'])) { //display teacher login page 
            include("joinClassTeacher.html");
        } else if (isset($_GET['teacherView'])) { //display teacher user page 
            fetchAverageData($db);
            fetchAllGradeData($db);
            include("teacherView.html");
        } else if (isset($_GET['studentView'])) { //display student user page 
            getStudentData($db);
            $_SESSION['questions'] = getQuestions($db, $_SESSION['accessCode']);
            var_dump($_SESSION['questions']);

            $id = 0;
            echo "<form method='POST'>";
            foreach ($_SESSION['names'] as $names) {
                echo "<h3>Group: $names </h3><br>";
                echo "<label for='1eval{$_SESSION['groupIds'][$id]}'>{$_SESSION['questions'][0]}:\t\t</label>";
                echo "<input name='1eval{$_SESSION['groupIds'][$id]}' type='number' min=1 max=5 required><br>";
                echo "<label for='2eval{$_SESSION['groupIds'][$id]}'>{$_SESSION['questions'][1]}:\t\t</label>";
                echo "<input name='2eval{$_SESSION['groupIds'][$id]}' type='number' min=1 max=5 required><br>";
                echo "<label for='3eval{$_SESSION['groupIds'][$id]}'>{$_SESSION['questions'][2]}:\t\t</label>";
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
        } else if (isset($_GET['download'])) {
            $accessCode = $_SESSION['accessCode'];
            $password = checkPassword($db, $accessCode);
            $classInfo = "Access Code: " . $accessCode . " Password: " . $password[0];
            //print_r($classInfo);
            error_reporting(E_ALL);
            ini_set('display_errors', true);

            $file = fopen('/classInfo.txt', 'w'); //or die("Unable to open file!"); 
            var_dump($file);
            
            include("teacherView.html");
        } else if (isset($_GET['help'])) {
            include("fileHelp.html");
        } else {                            //display main page
            include("home.html");
        }

        //handle POST requests 
        if(isset($_POST['submitFile'])) {   //handle file upload 
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
                $questions = [$_POST['q1'], $_POST['q2'], $_POST['q3']];
                createClassroom($db, $groups, $questions);
                header("Refresh: 0; url=index.php?teacherView"); 
            }
        } else if (isset($_POST['userType'])) { //hander user type form
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
        } else if (isset($_POST['teacherLogin'])) { //handle teacher login form
            $givenPass = $_POST['password'];
            $result = checkPassword($db, $_SESSION['accessCode']);
            $pass = $result['classPassword'];
            if ($givenPass == $pass) {
                header("Refresh: 0; url=index.php?teacherView");
            } else {
                echo "<p>Password given does not match password on record, please try again. </p>";
            }
        } else if (isset($_POST['studentLogin'])) { //handle student login form
            $_SESSION['westernId'] = $_POST['westernId'];
            $_SESSION['studentName'] = $_POST['students'];
            header("Refresh: 0; url=index.php?studentView");
        } else if (isset($_POST['eval'])) {  //handle student completing eval 
            storeEval($db, $_POST);
            header("Refresh: 0; url=index.php?complete"); //not working
        }
    } catch (PDOException $e) {
    }

    include("footer.html");
?>
