<!DOCTYPE html>
<html>

    <head>
        
        <title>Welcome Week Session Booking</title>
        <style>
        
            body {
                background-color: gray;
            }
        
        </style>
        
    </head>
    <body>
    
        <h1>Welcome Week Session Booking Page</h1>
        <h3>Press 'Clear' twice to change topic selection or to start over.</h3>
        
        <?php
    
            $db_hostname = "mysql";
            $db_database = "u6cc";
            $db_username = "u6cc";
            $db_password = "mullac9278";
            $db_charset = "utf8mb4";
        
            $dsn = "mysql:host=$db_hostname;dbname=$db_database;charset=$db_charset";
            
            $opt = array (
            
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
                
            );
        
            session_start();
        
            try {
                
                $pdo = new PDO($dsn, $db_username, $db_password, $opt);
                
                $fullTopics = array();
                $atLeastOneTopicAvaliable = false;
                
                //Executes the query with the database.
                $stmt = $pdo->query("SELECT topic FROM topics"); 
                
                //Loop that runs once for each topic name. // This loop checks to see if a specific topic has no space left at all the times that it is running. If the topic has got no space left at all times it runs then the name of that topic is added to the full topics array.
                foreach($stmt as $topic) { 
                    
                    //Query that gets the times at which the current topic runs.
                    $getTimesQuery = "SELECT times FROM topics WHERE topic = '" . $topic["topic"] . "'"; 
                    
                    //Executes the query with the database.
                    $stmt2 = $pdo->query($getTimesQuery); 
                    
                    //Runs once.
                    foreach ($stmt2 as $times){
                        //Splits the string containing the times at which the topic runs.
                        $timesToBeChecked = explode(";", $times["times"]); 
                    }
                    
                    //Variable that will be used to hold the capacity of the current topic.
                    $topicCapacity;
                    
                    //Query that gets the capacity of the current topic.
                    $getTopicCapacityQuery = "SELECT capacity FROM topics WHERE topic = '" . $topic["topic"] . "'";
                    
                    //Executes the query with the database.
                    $stmt3 = $pdo->query($getTopicCapacityQuery);
                    
                    //Runs once.
                    foreach ($stmt3 as $capacity) {
                        //Sets $topicCapacity equal to the capacity of the topic returned by the query.
                        $topicCapacity = $capacity["capacity"];
                    }
                    
                    //Boolean for seeing if a topic is avaliable. This is set to false by default.
                    $topicAvaliable = false;
                    
                    //Loop that runs once for each specific time that the current topic runs.
                    foreach ($timesToBeChecked as $time) {
                        
                        //Query that gets the number of entrys for the current topic and the current time.
                        $getNumBookedQuery = "SELECT count(*) FROM bookings WHERE timeSlot = '" . $time . "' AND topic = '" . $topic["topic"] . "'";
                        
                        //Prepares the query.
                        $stmt3 = $pdo->prepare($getNumBookedQuery);
                        
                        //Executes the query with the database.
                        $stmt3->execute();
                        
                        //sets $getNumBooked equal to the number returned.
                        $getNumBooked = $stmt3->fetchColumn();
                        
                        // if the number booked for this time and topic is not greater then or equal to the topics capacity then...
                        if (!($getNumBooked >= $topicCapacity)) {
                            // set these booleans equal to true.
                            $atLeastOneTopicAvaliable = true;
                            $topicAvaliable = true;
                        }
                        
                    }
                    
                    // if the topic just checked had no avaliable spaces at all of the times that it runs then...
                    if (!$topicAvaliable) {
                        //push the name of that topic into an array for use later.
                        array_push($fullTopics, $topic["topic"]);
                    }
                }
                
                if ($atLeastOneTopicAvaliable) {
                    
                    //Array that will be used to hold the names of all topics.
                    $topicNames = array();
                    
                    //Query for getting all topic names from the database.
                    $stmt = $pdo->query("SELECT topic FROM topics");

                    //Basic HTML for start of form and select.
                    echo "<form name=\"form1\" method=\"post\">";
                    echo "<select name=\"topics\" onChange=\"this.form.submit()\">";
                    
                    //HTML for the first option in the drop down. This will not be selectable by the user and will be used as a placeholder.
                    echo "<option selected disabled hidden>Select a Topic</option>";
                    
                    //Loop that runs once for each topic name.
                    foreach ($stmt as $topicName) {
                        
                        //If the current topic name is not found in the $fullTopics array then...
                        if (!(in_array($topicName["topic"], $fullTopics))) {
                            
                            //Create an option for that topic name with a value of the name of the topic.
                            echo "<option value=\"";
                            echo $topicName["topic"];
                            echo "\">";
                            echo $topicName["topic"];
                            echo "</option>";
                        }
                        
                        //Pushes the current topic name into a topic names array.
                        array_push($topicNames, $topicName["topic"]);
                    }
                    
                    //HTML for ending the form and select.
                    echo "</select>";
                    echo "</form>";

                    //Session variable that will be used to hold the topic that was selected by the user.
                    $_SESSION["topicSelected"] = $_SESSION["topicSelected"] . $_POST['topics'];
                    
                    //Query for getting the times at which the topic selected by the user runs.
                    $timesQuery = "SELECT times FROM topics WHERE topic = '" . $_SESSION["topicSelected"] . "'";
                    
                    //Executes the query with the database.
                    $stmt = $pdo->query($timesQuery);
                    
                    //runs once.
                    foreach ($stmt as $time) {
                        //Splits the times returned from that query into an array that holds each time.
                        $times = explode(";", $time["times"]);
                    }
                    
                    // An array that will hold the times that are at full capacity and not avaliable to book.
                    $timesNotAvaliable = array();
                    
                    // for each time of the topic selected.
                    foreach ($times as $time) {
                        
                        //Query for returning the number of bookings for the topic selected at the current time we are looking at in the loop.
                        $specificTimeNumBooked = "SELECT count(*) FROM bookings WHERE timeSlot = '" . $time . "' AND topic = '" . $_SESSION["topicSelected"] . "'";
                        
                        //prepare statement.
                        $stmt = $pdo->prepare($specificTimeNumBooked);
                        
                        //execute statement.
                        $stmt->execute();
                        
                        //create variable to hold the number returned by the query.
                        $numBooked = $stmt->fetchColumn();
                        
                        //Query that will return the capacity of the topic selected.
                        $getTopicCapacity = "SELECT capacity FROM topics WHERE topic = '" . $_SESSION["topicSelected"] . "'";
                        
                        //execute
                        $stmt = $pdo->query($getTopicCapacity);
                        
                        //runs once.
                        foreach ($stmt as $capacity) {
                            //Set $topicCapacity equal to the capacity the query returned.
                            $topicCapacity = $capacity['capacity'];
                        }
                        
                        // if The number booked for this topic is greater then or equal to the capacity of the topic then...
                        if ($numBooked >= $topicCapacity) {
                            // push the current time into the $timesNotAvaliable array.
                            array_push($timesNotAvaliable, $time);
                        }
                    }
                    
                    //HTML for another form and select.
                    echo "<form name=\"form2\" method=\"post\">";
                    echo "<select name=\"times\">";
                    
                    //HTML for the first option in the drop down. This will not be selectable by the user and will be used as a placeholder.vv
                    echo "<option selected disabled hidden>Select a time</option>";
                    
                    //Loop that runs once for each time the selected topic runs.
                    foreach ($times as $time) {
                        
                        // If the current time is not in the $timeNotAvaliable array (e.g. it is avaliable) then...
                        if (!(in_array($time, $timesNotAvaliable))) {
                            
                            //create an option for that time and set its value to the time.
                            echo "<option value=\"";
                            echo $time;
                            echo "\">";
                            echo $time;
                            echo "</option>";

                        }
                        
                    }
                    
                    //HTML to get select.
                    echo "</select>";
                    
                    //HTML to create the input for the name and email and to create the clear and comfirm buttons.
                    echo "<br>Name: <input type=\"text\" name=\"name\" size=\"100\">";
                    echo "<input type=\"submit\" name=\"clear\" value=\"Clear\"><br>";
                    echo "Email: <input type=\"text\" name=\"email\" size=\"100\">";
                    echo "<input type=\"submit\" name=\"confirm\" value=\"Confirm\">";
                    
                    //HTML to end form.
                    echo "</form>";
                    
                    echo "Name: ";
                    echo $_POST['name'];
                    echo "<br>";
                    echo "Email: ";
                    echo $_POST['email'];
                    echo "<br>";
                    echo "Topic Selected: ";
                    echo $_SESSION["topicSelected"];
                    echo "<br>";
                    echo "Time Selected: ";
                    echo $_POST['times'];
                    echo "<br>";
                    
                    //If the clear button is pressed.
                    if (isset($_REQUEST['clear'])) {
                        
                        //clear the session variable topic selected.
                        $_SESSION["topicSelected"] = null;
                        //destroy the session.
                        session_destroy();
                        //clear the post array.
                        $_POST = array();
                    }
                    
                    //If the confirm button is clicked.
                    if (isset($_REQUEST['confirm'])) {
                        
                        //If the string contained in the session variable 'topicSelected' is not found in the $topicNames array.
                        if (!(in_array($_SESSION["topicSelected"], $topicNames))) {
                            //Tell the user that something went wrong and how to start over.
                            echo "<h1>Something has gone wrong. Please press 'clear' twice to start again.</h1>";
                        }
                        
                        //Creating boolean variables for checking that everything is valid. These are all set to true by default.
                        $validName = true;
                        $validEmail = true;
                        $timeSelected = true;
                        
                        //If the user has not selected a time then...
                        if (!isset($_POST['times'])) {
                            //Ask the user to select a time.
                            echo "<h1>You must select a time.</h1><br>";
                            $timeSelected = false;
                        }
                        
                        // If the name entered by the user contains some character other then a letter, hyphen, apostrophe or space then...
                        if (!preg_match("/^[a-zA-Z-' ]*$/", $_POST['name'])) {
                            //Tell the user what is wrong with the name they have entered.
                            echo "<h1>Name must only contain either letters, hyphens, apostrophes or spaces.</h1><br>";
                            //set $validName equal to false.
                            $validName = false;
                        } 
                        // If the name entered by the user contains a sequence of two or more hyphens or apostrophes then...
                        if (preg_match("/(-|'){2,}/", $_POST['name'])) {
                            //Tell the user what is wrong with the name they have entered.
                            echo "<h1>Name must not contain any sequence of two or more hyphens or apostrophes.</h1><br>";
                            //set $validName equal to false.
                            $validName = false;
                        }
                        // If the name entered by the user starts with some character other then a letter or apostrophe then...
                        if (!preg_match("#^['a-zA-Z]#", $_POST['name'])) {
                            //Tell the user what is wrong with the name they have entered.
                            echo "<h1>Name must either start with a letter or an apostrophe.</h1><br>";
                            //set $validName equal to false.
                            $validName = false;
                        }
                        // If the email entered by the user is not valid.
                        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                            //Tell the user that the email they entered is not valid.
                            echo "<h1>Email is not valid.</h1><br>";
                            //Set $validEmail equal to false.
                            $validEmail = false;
                        }
                        
                        // If the name and email entered by the user are valid and the user has selected a time then...
                        if ($validName and $validEmail and $timeSelected) {
                            
                            //Statement for inserting the data entered by the user into the database. Placeholders are currently being used in place of the actual data.
                            $stmt = $pdo->prepare("INSERT INTO bookings (topic,studentName,studentEmail,timeSlot) VALUES (:topic,:studentName,:studentEmail,:timeSlot)");
                            
                            //Replaces the placeholds with the actual data and executes the query.
                            $stmt->execute(array("topic" => $_SESSION["topicSelected"], "studentName" => $_POST['name'], "studentEmail" => $_POST['email'], "timeSlot" => $_POST['times']));

                            echo "<h1>CONFIRMED</h1>";
                            
                        } else {
                            
                            //Else the booking was unsuccessful.
                            echo "<h1>Your Booking was unsuccessful.</h1>";
                        }
                    }
                
                } else {
                    //No topics avaliable.
                    echo "<h1>No topics are avaiable to book.</h1>";
                }
                
            } catch (PDOException $e) {
                
                exit("PDO Error: ".$e->getMessage()."<br>");
                
            }
        ?>
        
    </body>
</html>