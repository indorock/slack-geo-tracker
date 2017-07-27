<?php
if(@$_COOKIE['email']){
    echo 'hi there '.$_COOKIE['email']." I remember you! You already submitted your form";
    die();
}
if($_POST){
    $email = $_POST['email'];
    setcookie('email',  $email, time()+3600);
    echo 'HEY THERE '.$email;
    
}else{
?>

<form action="./hi.php" method="post">
    <input type="email" name="email">
    <input type="submit" value="GO">
</form>

<?php } ?>