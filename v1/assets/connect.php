<?php 
if(session_id() == '') {
    session_start();
}

require_once('controllers/all_controllers.php');
require_once('models/all_models.php');
