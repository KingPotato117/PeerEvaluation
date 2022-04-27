<?php
    require("model.php");
         
    session_set_cookie_params(4000, '/');          
    session_start();

    $dsn = 'mysql:host=us-cdbr-east-05.cleardb.net;dbname=heroku_a500c3e55d9d3ba';
    $dbusername = 'bc63908529504f';
    $dbpassword = '6cf43e72';

    var_dump($_SESSION['loggedIn']);
    include("navBar.html");

    try {
        error_reporting(E_ALL);
        ini_set('display_errors', true);

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

            foreach ($students as $student) {
                echo "<option value='". $student["studentName"] . "' >". $student["studentName"] .  "</option>";
            }
            echo "<br/>";
            echo "<input type='submit' name='studentLogin' />";
            echo "</select>";
            echo "</form>";
        } else if (isset($_GET['teacher'])) { //display teacher login page 
            include("joinClassTeacher.html");
        } else if (isset($_GET['teacherView']) && isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] == True) { //display teacher user page 
            fetchAverageData($db);
            fetchAllGradeData($db);
            include("teacherView.html");
        } else if ( (isset($_GET['teacherView']) || isset($_GET['studentView'])) && (isset($_SESSION['loggedIn']) == False || $_SESSION['loggedIn'] == False)) {     //handle user going directly to teacherView w/o logging in
            echo "<h3>Not Logged In... Please Go Back and Log In...</h3>";
        } else if (isset($_GET['studentView'])) {       //display student user page 
            getStudentData($db);
            $_SESSION['questions'] = getQuestions($db, $_SESSION['accessCode']);
            $_SESSION['graderGId'] = getUserGroup($db);
            $i = 0;
            ob_start();
            echo "<form method='POST'>";
            if (($key = array_search($_SESSION['graderGId'], $_SESSION['groupIds'])) !== false) {
                unset($_SESSION['groupIds'][$key]);
            }
            foreach ($_SESSION['groupIds'] as $id) {
                echo "<h3>Group: ".$_SESSION['names'][$i] ."</h3><br>";
                echo "<label for='1eval$id'>{$_SESSION['questions'][0]}:\t\t</label>";
                echo "<input name='1eval$id' type='number' min=1 max=5 required><br>";
                echo "<label for='2eval$id'>{$_SESSION['questions'][1]}:\t\t</label>";
                echo "<input name='2eval$id' type='number' min=1 max=5 required><br>";
                echo "<label for='3eval$id'>{$_SESSION['questions'][2]}:\t\t</label>";
                echo "<input name='3eval$id' type='number' min=1 max=5 required><br>";
                echo "<label for='comment$id'>Comments:\t\t</label>";
                echo "<input name='comment$id' type='text'><br>";
                echo "<input name='complete' type='hidden'>";
                echo "<br>";
                   
                $i++;
            }
            echo "<input type='submit'>";
            echo "</form>";
        } else if (isset($_GET['complete'])) { //display complete page 
            include("complete.html"); 
        } else if (isset($_GET['download'])) {
            $accessCode = $_SESSION['accessCode'];
            $password = $_SESSION['pass'];
            $classInfo = "Access Code: " . $accessCode . " Password: " . $password;

            $fp = fopen('/tmp/classInfo.txt', 'w') or die("Unable to open file!"); 
            $size = fwrite($fp, $classInfo);
            fclose($fp);

            $file = "/tmp/classInfo.txt";
            if (file_exists($file)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename='.basename($file));
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file));
                ob_end_clean();
                readfile("/tmp/classInfo.txt");
                exit;
            }
        } else if (isset($_GET['help'])) {
            include("fileHelp.html");
        } else if (isset($_GET['logOut'])) {
            $_SESSION['loggedIn'] = False;
            include("home.html");
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
                $_SESSION['loggedIn'] = True;
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
            $hashPass = $result['classPassword'];
            if (password_verify($givenPass, $hashPass)) {
                $_SESSION['pass'] = $givenPass;
                $_SESSION['loggedIn'] = True;
                header("Refresh: 0; url=index.php?teacherView");
            } else {
                echo "<p>Password given does not match password on record, please try again. </p>";
            }
        } else if (isset($_POST['studentLogin'])) { //handle student login form 
            $_SESSION['westernId'] = $_POST['westernId'];
            $_SESSION['studentName'] = $_POST['students'];
            $_SESSION['loggedIn'] = True;
            header("Refresh: 0; url=index.php?studentView");
        } else if (isset($_POST['complete'])) {  //handle student completing eval
            ob_clean();
            storeEval($db, $_POST);
            $_SESSION['loggedIn'] = False;
            header("Refresh: 0; url=index.php?complete");
        }
    } catch (PDOException $e) {
        print($e);
    }

    include("footer.html");
