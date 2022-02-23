<?php
    require("model.php");
    include("navBar.html");

    if (isset($_GET["createClass"])) {  //display createClassroom page 
        include("createClass.html");
    } else if(isset($_GET["joinClass"])) {
        include("joinClass.html");
    } else {                            //display main page 
        include("home.html");
    }

    include("footer.html");
?>