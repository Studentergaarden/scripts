#!/usr/bin/php -q
<?php
    //If  the  file /usr/local/sbin/deluser.local exists, it will be executed after the user account has been removed in order to do any local cleanup. The arguments passed to deluser.local are: username uid gid home-directory
    $username=$argv[1];


     system("deluser --remove-home $username");
     //connect to database
     require "/root/scripts/dbconnect.inc.php";

     $result = mysql_query("SELECT user_id, name_id FROM user WHERE user.user = '$username'") or die(mysql_error());
     $onerow = mysql_fetch_array($result);
     $nameid = $onerow['name_id'];
     $userid = $onerow['user_id'];
print "nameid: $nameid\n";
    print "userid: $userid\n";

     mysql_query("DELETE FROM grp_user WHERE grp_user.user_id = '$userid'") or die(mysql_error());
     mysql_query("DELETE FROM grp_user_log WHERE grp_user_log.user_id = '$userid'") or die(mysql_error());
     mysql_query("DELETE FROM info WHERE info.name_id = '$nameid'") or die(mysql_error());
     mysql_query("DELETE FROM name_log WHERE name_log.name_id = '$nameid'") or die(mysql_error());
     mysql_query("DELETE FROM user_log WHERE user_log.user_id = '$userid'") or die(mysql_error());
     mysql_query("DELETE FROM print_log WHERE print_log.name_id = '$nameid'") or die(mysql_error());
     mysql_query("DELETE FROM name WHERE name.name_id = '$nameid'") or die(mysql_error());
     mysql_query("DELETE FROM user WHERE user.user_id = '$userid'") or die(mysql_error());

     //sletter brugeren fra samba, sambas egen function kan ikke bruges da
     // den forventer at useren findes i passwd, men der er brugeren allerede slettet
     system("sed -i -e \"/$username:$userid:/d\" /etc/smbpasswd");

     // sletter maildir
     system("rm -rf /var/mail/maildirs/studentergaarden.dk/$username");
?>