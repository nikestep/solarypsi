<?php
    $stmt = $db_link->prepare ("SELECT " .
                               "    title, " .
                               "    link_desc, " .
                               "    visible_link, " .
                               "    full_link " .
                               "FROM " .
                               "    website_link " .
                               "ORDER BY " .
                               "    disp_order");
    $stmt->execute ();
    $stmt->bind_result ($title,
                        $desc,
                        $visible_link,
                        $full_link);

    while ($stmt->fetch ()) {
        echo "<p>\n";
        echo "<strong>$title</strong>\n";
        if ($desc !== NULL) {
            echo "($desc)\n";
        }
        echo "</p>\n";
        echo "<p><a href='$full_link' target='_blank'>$visible_link</a></p>\n";
    }

    $stmt->close ();
?>
