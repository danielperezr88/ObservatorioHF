<?php
  require_once 'libs/travis-ic/php-encryption_v04.2016/autoload.php';
  include "libs/paragonie/password-lock_v04.2016/PasswordLock.php";
  include "libs/paragonie/constant-time-encoding_v04.2016/Binary.php";
  include "libs/paragonie/constant-time-encoding_v04.2016/EncoderInterface.php";
  include "libs/paragonie/constant-time-encoding_v04.2016/Base64.php";
	//onsubmit="return checkForm(this);"

  use \ParagonIE\PasswordLock\PasswordLock;
  use \Defuse\Crypto\Crypto;
	
	$email = GetPost("email", "");
	$pwd1 = GetPost("pwd1", "");
	if ($email && is_string($email) && $pwd1 && is_string($pwd1))
	{

    if($UserRows = $sql_tools->GetUserData($email))
    {
      $User = array_values($UserRows)[0];

      if(!$User)  break;

      if (PasswordLock::decryptAndVerify($pwd1, hex2bin(utf8_encode($User["hashed_info"])), hex2bin(utf8_encode($User["spyce"])))) {
        $userData = array("name" => $User["name"], "id" => $User["user_id"]);
        SetLoggedUser($userData);
        //setcookie($cookie_name, serialize($userData), time() + (86400 * 30), "/");
        header("Location: index.php");
      }
    }
	}
?>
<body>
<style>
	.login
	{
		color: black;
		width: 300px;
	}
  .login-logo
  {
    padding: 50px 0 50px 0;
  }
</style>

<div class="login-logo"><center><img src="img/hf-logo-trasnparent.png"  height="80" alt="Human Forecast"></center></div>

<div class="maincontent">
<center>
<form class="well login" enctype="multipart/form-data" method="post" >
  <table>
    <tbody>
      <tr>

        <td colspan="2"><h3>System access:</h3></td>

      </tr><tr>
        
        <td colspan="2">
          <font color="red"></font>
        </td>

      </tr><tr>
        
        <td>
          <label for="email">Email:</label>
        </td>

        <td>
    	    <input type="email" id="email" name="email" class="validate-email" size="30" value="" required="">
    	  </td>

    	</tr><tr>
    	  
        <td>
          <p><label for="pwd1">Password:</label></p>
        </td>

        <td>
    	   <input type="password" name="pwd1" id="pwd1" class="validate-password" size="30" value="" required="">
    	  </td>

    	</tr><tr>

    	  <td colspan="2">
          <input type="submit" value="Acceder" class="btn">
      	</td>

    	</tr>
    </tbody>
  </table>
</form>
</center>
</div>
</body>