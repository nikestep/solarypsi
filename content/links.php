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
		echo "<div class='link-row'>\n";
		echo "<div>\n";
		echo "<span class='bold'>$title</span>\n";
		if ($desc !== NULL) {
			echo "<span>($desc)</span>\n";
		}
		echo "</div>\n";
		echo "<div><a href='$full_link' target='_blank'>$visible_link</a></div>\n";
		echo "</div>\n";
	}

	$stmt->close ();
?>
