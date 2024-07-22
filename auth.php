<?php

function Auth($authorization) {
    // Ensure that the authorization string contains a space
    if (strpos($authorization, ' ') === false) {
        return false; // Invalid format
    }

    list($type, $token) = explode(' ', $authorization, 2);
    
    // Optionally, you could add more checks for the type, e.g., Bearer
    // if (strtolower($type) !== 'bearer') {
    //     return false;
    // }

    $valid_tokens = array(
        'FREETOKEN',
        // Add other valid tokens here if needed
    );

    return in_array($token, $valid_tokens);
}

?>
