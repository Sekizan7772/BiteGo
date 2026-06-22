<?php
include("db.php");
include("sweet.html");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($link, $_POST["email"]);
    $description = mysqli_real_escape_string($link, $_POST["description"]);
    $proposed_date = mysqli_real_escape_string($link, $_POST["proposed_date"]);
    $requirements = isset($_POST["requirements"]) ? 1 : 0;

    $check = mysqli_query($link,"SELECT email FROM vendor_applications WHERE email = '$email' ");
    if(mysqli_fetch_row($check)>0){
        echo"
            <script>
            setTimeout(function() {
            Swal.fire({
                title: 'Application Failed!',
                text: 'Application failed! Please contact our admin for further help.',
                icon: 'warning',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            }).then(function() {
                window.location.href = 'frontpage.php';
            });
        }, 100);
    </script>";
    }
    else{
            $sql = "INSERT INTO vendor_applications (Email, Description, ProposedDate, RequirementsMet) 
            VALUES ('$email', '$description', '$proposed_date', '$requirements')";
            

            if (mysqli_query($link, $sql)) {


    }
            echo "
            <script>
                setTimeout(function() {
                Swal.fire({
                title: 'Success!',
                text: 'Application sent successfully! Our admin team will review it shortly.',
                icon: 'success',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            }).then(function() {
                window.location.href = 'frontpage.php';
            });
        }, 100);
    </script>";
}

    }

?>