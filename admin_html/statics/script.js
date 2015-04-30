/**
 * This is the Javascript file that drives the SolarYpsi administration portal.
 
 * @author Nik Estep
 * @date March 2, 2013
 */

// Declare global variables
var g_newSiteIDValidationIndex = 0;
var g_newSiteIDIsValid = false;
var g_newSiteIDValidationComplete = false;
var g_test = undefined;
var g_cron = undefined;

/**
 * Perform page startup operations once the document is ready.
 */
$(function() {
    // Construct the jQuery UI tab set
    $(".tabs").tabs ();
    
    // Stylize all buttons
    $("input[type='button']").button ();
    
    // Set up the sortable lists
    $(".sortable-list").sortable ();
    $(".sortable-list").disableSelection ();
    
    // Build WYSIWYG editors
    $("#txaPresentationFooter").wysiwyg ({
        autoSave: true
    });
    /*$("#txaEvents, #txaAbout, #txaContact").wysiwyg ({
        autoSave: true
    });*/
    $("#txaEvents").wysiwyg ({
        autoSave: true
    });
    $("#txaAbout").wysiwyg ({
        autoSave: true
    });
    $("#txaContact").wysiwyg ({
        autoSave: true
    });
    
    // Enable hover for edit/delete icons
    setIconHover ();
    
    // Bind element events
    bindEvents ();
    bindIconEvents ();
});

/**
 * Bind all events to their respective elements.
 */
function bindEvents () {
    // Automatically validate the new site ID
    $("#txtNewSiteID").on ('change', function (event) {
        if ($("#txtNewSiteID").val () === 'SELECT') {
            $("#spnNewSiteIDValid").removeClass ('inputValid');
            $("#spnNewSiteIDValid").addClass ('inputError');
            return;
        }
        
        // Set that the request is pending
        g_newSiteIDValidationComplete = false;
        
        // Set the request index for this validation pass
        // The purpose of this is to allow us to ignore old requests if the user
        //   has made multiple changes and triggered this event rapidly. Only
        //   the most recent request result should be displayed to the user.
        var requestIdx = (++g_newSiteIDValidationIndex);
        
        $.ajax ({
            url: 'ajax/newSiteIDValid.php',
            method: 'POST',
            data: {
                requestIndex: requestIdx,
                siteID: $("#txtNewSiteID").val ()
            },
            dataType: 'json',
            success: function (data) {
                if (data.requestIndex === g_newSiteIDValidationIndex) {
                    if (data.isValid) {
                        // Display checkmark
                        showSuccess ("#spnNewSiteIDValid");
                    }
                    else {
                        // Display 'X'
                        showError ("#spnNewSiteIDValid");
                    }
                    
                    g_newSiteIDValidationComplete = true;
                }
            },
            error: function () {
                g_newSiteIDIsValid = false;
            }
        });
    });
    
    // Create a new site
    $("#btnCreateSite").on ('click', function (event) {
        // Validate the inputs
        if ($.trim ($("#txtNewSiteID").val ()) === '') {
            alert ('Please enter a site ID.');
            return;
        }
        
        if ($.trim ($("#txtNewSiteDesc").val ()) === '') {
            alert ('Please enter a new site description.');
            return;
        }
        
        $.ajax ({
            url: 'ajax/saveNewSite.php',
            method: 'POST',
            data: {
                siteID: $("#txtNewSiteID").val (),
                description: $("#txtNewSiteDesc").val ()
            },
            dataType: 'json',
            success: function (data) {
                if (data.result) {
                    // Clear the forms
                    $("#txtNewSiteID").val ('');
                    $("#spnNewSiteIDValid").removeClass ('inputValid');
                    $("#spnNewSiteIDValid").removeClass ('inputError');
                    $("#txtNewSiteDesc").val ('');
                    $("#selSites").find ('option').remove ();
                    $('<option>').val ('SELECT')
                                 .html ('-- Select a Site')
                                 .appendTo ($("#selSites"));
                    
                    // Re-populate the sites drop down list
                    $.each (data.sites, function (id, desc) {
                        $('<option>').val (id)
                                     .html (desc)
                                     .appendTo ($("#selSites"));
                    });
                    
                    // Select the just created site and fire the even to load
                    // the site for edit
                    $("#selSites").val (data.siteID);
                    $("#btnEditSite").click ();
                }
                else {
                    alert ('An error occurred: ' + data.error_msg);
                }
            },
            error: function () {
                alert ('An unknown error has occurred.');
            }
        });
    });
    
    // Hide the site edit div when a new site is selected
    $("#selSites").on ('change', function (event) {
        $("#dvEditSite").hide ();
    });
    
    // Edit a site
    $("#btnEditSite").on ('click', function (event) {
        if ($("#selSites").val () === 'SELECT') {
            return;
        }
        
        $.ajax ({
            url: 'ajax/siteDetails.php',
            method: 'POST',
            data: {
                siteID: $("#selSites").val ()
            },
            dataType: 'json',
            success: function (data) {
                // Populate basic information fields
                $("#selType").val (data.inst_type);
                $("#txtCompleted").val (data.completed);
                $("#txtNumberPanels").val (data.panel_desc);
                $("#txtAnglePanels").val (data.panel_angle);
                $("#txtInverter").val (data.inverter);
                $("#txtOutput").val (data.rated_output);
                $("#txtInstaller").val (data.installer);
                $("#txtInstallerURL").val (data.installer_url);
                $("#txtContact").val (data.contact);
                $("#txtContactURL").val (data.contact_url);
                $("#txaList").val (data.list_desc);
                $("#selStatus").val (data.status);
                $("#selInCity").val (data.loc_city);
                $("#txtLatitude").val (data.loc_lat);
                $("#txtLongitude").val (data.loc_long);
                $("#txtMaxWH").val (data.max_wh);
                $("#txtMaxKW").val (data.max_kw);
                $("#selMeteringType").val (data.meter_type);
                $("#txtQR").val (data.qr_code);
                
                // Render resource sets
                renderDocumentOrReportSortable ('ulDocumentSort', data.doc_link);
                renderDocumentOrReportSortable ('ulReportSort', data.report);
                renderImageSortable ('ulImageSort', data.image, data.base_url);
                
                // Show the block
                $("#dvEditSite").show ();
                $("#spnSiteEditLabel").html ($("#selSites option:selected").text ());
                $(".frmHiddenSiteID").val ($("#selSites").val ());
            },
            error: function () {
                alert ('An unknown error has occurred.');
            }
        });
    });
    
    // Save site basic information
    $("#btnSaveBasic").on ('click', function (event) {
        // Validate the inputs first
        //   At this time, just check that numeric values are blank or a valid
        //   number
        if ($("#txtOutput").val () !== '' && isNaN (parseInt ($("#txtOutput").val ()))) {
            alert ('Rated output must be a valid integer number.');
            return;
        }
        if ($("#txtLatitude").val () !== '' && isNaN (parseFloat ($("#txtLatitude").val ()))) {
            alert ('Latitude must be a valid decimal number.');
            return;
        }
        if ($("#txtLongitude").val () !== '' && isNaN (parseFloat ($("#txtLongitude").val ()))) {
            alert ('Longitude must be a valid decimal number.');
            return;
        }
        if ($("#txtMaxWH").val () !== '' && isNaN (parseInt ($("#txtMaxWH").val ()))) {
            alert ('Max Wh must be a valid integer number.');
            return;
        }
        if ($("#txtMaxKW").val () !== '' && isNaN (parseFloat ($("#txtMaxKW").val ()))) {
            alert ('Max kW must be a valid decimal number.');
            return;
        }
        
        // Start building the data to send over
        var obj_data = {
            siteID: $("#selSites").val (),
            inst_type: $("#selType").val (),
            completed: $("#txtCompleted").val (),
            panel_desc: $("#txtNumberPanels").val (),
            panel_angle: $("#txtAnglePanels").val (),
            inverter: $("#txtInverter").val (),
            rated_output: $("#txtOutput").val (),
            installer: $("#txtInstaller").val (),
            installer_url: $("#txtInstallerURL").val (),
            contact: $("#txtContact").val (),
            contact_url: $("#txtContactURL").val (),
            list_desc: $.trim ($("#txaList").val ()),
            status: $("#selStatus").val (),
            loc_city: $("#selInCity").val (),
            loc_lat: $("#txtLatitude").val (),
            loc_long: $("#txtLongitude").val (),
            max_wh: $("#txtMaxWH").val (),
            max_kw: $("#txtMaxKW").val (),
            meter_type: $("#selMeteringType").val ()
        };
        
        // TODO: Add to the data information related to metering
        
        // Send the request
        $.ajax ({
            url: 'ajax/saveSiteDetails.php',
            method: 'POST',
            data: obj_data,
            dataType: 'json',
            success: function (data) {
                if (data.result) {
                    showSuccess ("#spnBasicSaveValid");
                }
                else {
                    alert ('An error occurred.\r\nMySQL Error Msg: ' +
                           data.err_mysql);
                    showError ("#spnBasicSaveValid");
                }
            },
            error: function () {
                alert ('An unknown error has occurred.');
                showError ("#spnBasicSaveValid");
            }
        });
    });
    
    // Events for uploading/saving resources
    $("#btnUploadDocument").on ('click', function (event) {
        $("#divPrgDocument").show ();
        uploadFile ('frmDocument', 'ulDocumentSort', 'divPrgDocument');
    });
    $("#btnUploadLink").on ('click', function (event) {
        uploadLink ('frmDocLinks', 'ulDocumentSort');
    });
    $("#btnUploadReport").on ('click', function (event) {
        $("#divPrgReport").show ();
        uploadFile ('frmReport', 'ulReportSort', 'divPrgReport');
    });
    $("#btnUploadImage").on ('click', function (event) {
        $("#divPrgImage").show ();
        uploadFile ('frmImage', 'ulImageSort', 'divPrgImage');
    });
    
    // Events for saving resource orderings
    $("#btnSaveDocuments").on ('click', function (event) {
        saveResourceOrdering ('ulDocumentSort', 'spnDocumentSortResult');
    });
    $("#btnSaveReports").on ('click', function (event) {
        saveResourceOrdering ('ulReportSort', 'spnReportSortResult');
    });
    $("#btnSaveImages").on ('click', function (event) {
        saveResourceOrdering ('ulImageSort', 'spnImageSortResult');
    });
    
    // Event for saving QR video embed ID
    $("#btnSaveQR").on ('click', function (event) {
        // Start building the data to send over
        var obj_data = {
            siteID: $("#selSites").val (),
            qr_code: $("#txtQR").val ()
        };
        
        // Send the request
        $.ajax ({
            url: 'ajax/saveQREmbedID.php',
            method: 'POST',
            data: obj_data,
            dataType: 'json',
            success: function (data) {
                if (data.result) {
                    showSuccess ("#spnQRResult");
                }
                else {
                    alert ('An error occurred.\r\nMySQL Error Msg: ' +
                           data.err_mysql);
                    showError ("#spnQRResult");
                }
            },
            error: function () {
                alert ('An unknown error has occurred.');
                showError ("#spnQRResult");
            }
        });
    });
    
    // Events for the link page
    $("#btnSaveLink").on ('click', function (event) {
        $.ajax ({
            url: 'ajax/saveLink.php',
            type: 'POST',
            data: {
                'title': $.trim ($("#frmLink input[name='title']").val ()),
                'description': $.trim ($("#frmLink input[name='description']").val ()),
                'visible_link': $.trim ($("#frmLink input[name='visible_link']").val ()),
                'full_link': $.trim ($("#frmLink input[name='full_link']").val ())
            },
            dataType: 'json',
            success: function (data) {
                if (data.success) {
                    // Build a new  element to append to the list
                    var li = $('<li>').addClass ('ui-state-default');
                    $('<span>').addClass ('sortable-hidden-id')
                               .addClass ('hidden')
                               .html (data.id)
                               .appendTo (li);
                    $('<span>').addClass ('ui-icon')
                               .addClass ('ui-icon-arrowthick-2-n-s')
                               .appendTo (li);
                    var span = $('<span>').addClass ('link-content')
                                          .appendTo (li);
                    var div = $('<div>').appendTo (span);
                    $('<span>').addClass ('bold')
                               .html (data.title)
                               .appendTo (div);
                    if (data.description !== null) {
                        $('<span>').html (' (' + data.description + ')')
                                   .appendTo (div);
                    }
					div = $('<div>').addClass ('url-and-edit')
								    .appendTo (span);
                    $('<a>').attr ('href', data.full_link)
                            .html (data.visible_link + ' ')
                            .appendTo ($('<span>').addClass('link-url')
												  .appendTo (div));
					var editSpan = $('<span>').addClass('link-edit')
											  .appendTo(div);
					var deleteSpan = $('<span>').addClass('edit-delete-icon-width')
												.addClass('action-delete-link')
												.addClass('ui-state-default')
												.addClass('ui-corner-all')
											    .on ('click', function (event) {
											    	deleteLink (li);
											    })
												.appendTo(editSpan);
					$('<span>').addClass('ui-icon')
							   .addClass('ui-icon-trash')
							   .html('&nbsp;')
							   .appendTo(deleteSpan);
                    li.appendTo ($("#ulLinkSort"));
                    
                    // Refresh the list
                    $("#ulLinkSort").sortable ('refresh');
                    
                    // Empty the form
                    $("#frmLink input[name='title']").val ('');
                    $("#frmLink input[name='description']").val ('');
                    $("#frmLink input[name='visible_link']").val ('');
                    $("#frmLink input[name='full_link']").val ('');
                    showSuccess ("#frmLink .upload-valid");
					
					// Re-bind events
					bindEvents();
                }
                else {
                    alert ('Unable to save link.\r\nMySQL Error Message: ' +
                           data.err_msg);
                }
            },
            error: function () {
                alert ('An unknown error has occurred');
            }
        });
    });
    
    $("#btnSaveLinks").on ('click', function (event) {
        // Build the ordered array
        var ordering = new Array ();
        var index = 0;
        $("#ulLinkSort").find ('li').each (function () {
            ordering[index++] = $(this).find ('span.sortable-hidden-id').html ();
        });
        
        // Send it to the server
        $.ajax ({
            url: 'ajax/saveLinkOrdering.php',
            method: 'POST',
            data: {
                orderings: ordering
            },
            dataType: 'json',
            success: function (data) {
                if (data.success) {
                    showSuccess ("#spnLinkSortResult");
                }
                else {
                    alert ('Unable to save ordering.\r\nMySQL Error Message: ' +
                           data.err_msg);
                    showError ("#spnLinkSortResult");
                }
            },
            error: function () {
                alert ('An unknown error has occurred.');
            }
        });
    });
	
	$(".action-delete-link").on ('click', function (event) {
		deleteLink ($(this).closest ("li"));
	});
    
    // Events for the presentation page
    $("#btnSavePresentationFooter").on ('click', function (event) {
        saveContentPage ('txaPresentationFooter', 'presentations_footer', 'spnContentPresentationFooterResult');
    });
    
    // Events for content page save buttons
    $("#btnSaveEvents").on ('click', function (event) {
        saveContentPage ('txaEvents', 'events', 'spnContentEventsResult');
    });
    $("#btnSaveAbout").on ('click', function (event) {
        saveContentPage ('txaAbout', 'about', 'spnContentAboutResult');
    });
    $("#btnSaveContact").on ('click', function (event) {
        saveContentPage ('txaContact', 'contact', 'spnContentContactResult');
    });
    
    // Edit event for the cron page
    $(".cron-edit").on ('click', function (event) {
        var row = $(this).closest ("tr");
        if (g_cron === undefined) {  // ui-icon-circle-check
            g_cron = {
                'row': row,
                'id': row.find ("td.name").html ()
            };
            var schedule = row.find ("td.schedule").html ();
            row.find ("td.schedule").html ('<input type="text" value="' + schedule + '" />');

            var enabled = row.find ("td.enabled").html ();
            row.find ("td.enabled").html ('<select><option value="Yes">Yes</option><option value="No">No</option></select>');
            row.find ("td.enabled select").val (enabled);

            g_cron.schedule = schedule;
            g_cron.enabled = enabled;

            $(this).find ("span").removeClass ('ui-icon-pencil');
            $(this).find ("span").addClass ('ui-icon-circle-check');
        }
        else if (row.find ("td.name").html () === g_cron.id) {
            var name = g_cron.id;
            var schedule = g_cron.row.find ("td.schedule input").val ();
            var enabled = g_cron.row.find ("td.enabled select").val () === 'Yes' ? 1 : 0;
            $.ajax ({
                url: 'ajax/updateCron.php',
                type: 'POST',
                data: {
                    'name': name,
                    'schedule': schedule,
                    'enabled': enabled
                },
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        g_cron.row.find ("td.schedule").html (g_cron.row.find ("td.schedule input").val ());
                        g_cron.row.find ("td.enabled").html (g_cron.row.find ("td.enabled select").val ());
                        g_cron.row.find ("td.actions ul li.edit span").removeClass ('ui-icon-circle-check');
                        g_cron.row.find ("td.actions ul li.edit span").addClass ('ui-icon-pencil');
                        g_cron = undefined;
                    }
                    else {
                        alert ('Unable to make changes.\r\nError message: ' +
                               data.err_msg);
                    }
                },
                error: function () {
                    alert ('An unknown error has occurred.');
                },
                cache: false
            });
        }
        else {
            g_cron.row.find ("td.schedule").html (g_cron.schedule);
            g_cron.row.find ("td.enabled").html (g_cron.enabled);
            g_cron.row.find ("td.actions ul li.edit span").removeClass ('ui-icon-circle-check');
            g_cron.row.find ("td.actions ul li.edit span").addClass ('ui-icon-pencil');
            g_cron = undefined;
            $(this).click ();
        }
    });
}

/**
 * Upload a file from one of the forms on the page to the server.
 *
 * @param formID {String} DOM ID for form to upload from
 * @param ulID {String} DOM ID for sort list to add item to
 * @param divPrgID {String} DOM ID for DIV around progress bar
 */
function uploadFile (formID, ulID, divPrgID) {
    $.ajax ({
        url: 'ajax/fileUpload.php',
        type: 'POST',
        data: new FormData ($("#" + formID)[0]),
        dataType: 'json',
        success: function (data) {
            $("#" + divPrgID).hide ();
            
            if (data.success) {
                var li = $('<li>').addClass ('ui-state-default');
                
                if (data.type === 'image') {
                    var div = $('<div>').css ('vertical-align', 'center')
                                        .css ('margin-left', 'auto')
                                        .css ('margin-right', 'auto')
                                        .appendTo (li);
                    $('<span>').addClass ('sortable-hidden-id')
                               .addClass ('hidden')
                               .html (data.id)
                               .appendTo (div);
                    $('<img>').attr ('src', data.base_url + data.path)
                              .attr ('alt', data.title)
                              .css ('width', data.thumb_width)
                              .css ('height', data.thumb_height)
                              .appendTo ($('<div>').addClass ('img')
                                                   .appendTo (div));
                    $('<div>').addClass ('caption')
                              .html (data.desc)
                              .appendTo (div);
                }
                else {
                    $('<span>').addClass ('sortable-hidden-id')
                               .addClass ('hidden')
                               .html (data.id)
                               .appendTo (li);
                    $('<span>').addClass ('ui-icon')
                               .addClass ('ui-icon-arrowthick-2-n-s')
                               .appendTo (li);
                    $('<span>').addClass ('ui-icon')
                               .addClass ((data.type === 'link' ? 'ui-icon-link' : 'ui-icon-document'))
                               .attr ('title', (data.type === 'link' ? 'Link' : 'Document'))
                               .appendTo (li);
                    $('<span>').addClass ('sortable-doc-title')
                               .html (data.title)
                               .appendTo (li);
                    var spn = $('<span>').addClass ('sortable-doc-edit')
                                         .appendTo (li);
                    var ul = $('<ul>').addClass ('icons-edit-buttons')
                                      .addClass ('ui-widget')
                                      .addClass ('ui-helper-clearfix')
                                      .appendTo (spn);
                    /*$('<span>').addClass ('ui-icon')
                                 .addClass ('ui-icon-pencil')
                                 .appendTo ($('<li>').addClass ('edit')
                                                     .addClass ('ui-state-default')
                                                     .addClass ('ui-corner-all')
                                                     .attr ('title', 'Edit')
                                                     .appendTo (ul));*/
                    $('<span>').addClass ('ui-icon')
                               .addClass ('ui-icon-trash')
                               .appendTo ($('<li>').addClass ('doc-trash')
                                                   .addClass ('ui-state-default')
                                                   .addClass ('ui-corner-all')
                                                   .attr ('title', 'Delete')
                                                   .appendTo (ul));
                }
                
                li.appendTo ($("#" + ulID));
                $("#" + ulID).sortable ('refresh');
                
                $("#" + formID + " input[name='title']").val ('');
                $("#" + formID + " input[name='description']").val ('');
                $("#" + formID + " input[type='file']").val ('');
                setIconHover ();
                bindIconEvents ();
                showSuccess ("#" + formID + " .upload-valid");
            }
            else {
                alert ('Unable to upload file.\r\nPHP Error code: ' +
                       data.err_php +
                       '\r\nMySQL Error Msg: ' +
                       data.err_mysql);
                showError ("#" + formID + " .upload-valid");
            }
        },
        error: function () {
            alert ('An unknown error has occurred.');
        },
        cache: false,
        contentType: false,
        processData: false
    });
}

/**
 * 
 */
function uploadLink (formID, ulID) {
    $.ajax ({
        url: 'ajax/saveResourceLink.php',
        type: 'POST',
        data: new FormData ($("#" + formID)[0]),
        dataType: 'json',
        success: function (data) {
            if (data.success) {
                var li = $('<li>').addClass ('ui-state-default');
                
                $('<span>').addClass ('sortable-hidden-id')
                           .addClass ('hidden')
                           .html (data.id)
                           .appendTo (li);
                $('<span>').addClass ('ui-icon')
                           .addClass ('ui-icon-arrowthick-2-n-s')
                           .appendTo (li);
                $('<span>').addClass ('ui-icon')
                           .addClass ('ui-icon-link')
                           .appendTo (li);
                $('<span>').addClass ('sortable-doc-title')
                           .html (data.title)
                           .appendTo (li);
                var spn = $('<span>').addClass ('sortable-doc-edit')
                                     .appendTo (li);
                var ul = $('<ul>').addClass ('icons-edit-buttons')
                                  .addClass ('ui-widget')
                                  .addClass ('ui-helper-clearfix')
                                  .appendTo (spn);
                /*$('<span>').addClass ('ui-icon')
                             .addClass ('ui-icon-pencil')
                             .appendTo ($('<li>').addClass ('edit')
                                                 .addClass ('ui-state-default')
                                                 .addClass ('ui-corner-all')
                                                 .attr ('title', 'Edit')
                                                 .appendTo (ul));*/
                $('<span>').addClass ('ui-icon')
                           .addClass ('ui-icon-trash')
                           .appendTo ($('<li>').addClass ('doc-trash')
                                               .addClass ('ui-state-default')
                                               .addClass ('ui-corner-all')
                                               .attr ('title', 'Delete')
                                               .appendTo (ul));
                
                
                li.appendTo ($("#" + ulID));
                $("#" + ulID).sortable ('refresh');
                
                $("#" + formID + " input[name='title']").val ('');
                $("#" + formID + " input[name='description']").val ('');
                $("#" + formID + " input[name='link']").val ('');
                setIconHover ();
                bindIconEvents ();
                showSuccess ("#" + formID + " .upload-valid");
            }
            else {
                alert ('Unable to save link.\r\nMySQL Error Msg: ' +
                       data.err_mysql);
                showError ("#" + formID + " .upload-valid");
            }
        },
        error: function () {
            alert ('An unknown error has occurred.');
        },
        cache: false,
        contentType: false,
        processData: false
    });
}

/**
 * Iterate over the designated sortable UL and take note of the ordering of the
 * resources. Transmit the potentially new ordering to the server.
 *
 * @param ulID {String} DOM ID for UL to store ordering of
 * @param rsltSpnID {String} DOM ID for span to show result in
 */
function saveResourceOrdering (ulID, rsltSpnID) {
    // Build the ordered array
    var ordering = new Array ();
    var index = 0;
    $("#" + ulID).find ('li').each (function () {
        ordering[index++] = $(this).find ('span.sortable-hidden-id').html ();
    });
    
    // Send it to the server
    $.ajax ({
        url: 'ajax/saveResourceOrdering.php',
        method: 'POST',
        data: {
            orderings: ordering
        },
        dataType: 'json',
        success: function (data) {
            if (data.success) {
                showSuccess ("#" + rsltSpnID);
            }
            else {
                alert ('Unable to save ordering.\r\nMySQL Error Message: ' +
                       data.err_msg);
                showError ("#" + rsltSpnID);
            }
        },
        error: function () {
            alert ('An unknown error has occurred.');
        }
    });
}

/**
 * Render the list of sortable document resources.
 *
 * @param ulID {String} DOM ID for UL to render ordering in
 * @param data {Array<Object>} Resource objects to render
 */
function renderDocumentOrReportSortable (ulID, data) {
    // Clear the set
    $("#" + ulID).find ('li').remove ();
    
    // Iterate and build the group
    $.each (data, function (id, obj) {
        var li = $('<li>').addClass ('ui-state-default');
        $('<span>').addClass ('sortable-hidden-id')
                   .addClass ('hidden')
                   .html (id)
                   .appendTo (li);
        $('<span>').addClass ('ui-icon')
                   .addClass ('ui-icon-arrowthick-2-n-s')
                   .appendTo (li);
        $('<span>').addClass ('ui-icon')
                   .addClass ((obj.type === 'link' ? 'ui-icon-link' : 'ui-icon-document'))
                   .attr ('title', (obj.type === 'link' ? 'Link' : 'Document'))
                   .appendTo (li);
        $('<span>').addClass ('sortable-doc-title')
                   .html (obj.title)
                   .appendTo (li);
        var spn = $('<span>').addClass ('sortable-doc-edit')
                             .appendTo (li);
        var ul = $('<ul>').addClass ('icons-edit-buttons')
                          .addClass ('ui-widget')
                          .addClass ('ui-helper-clearfix')
                          .appendTo (spn);
        /*$('<span>').addClass ('ui-icon')
                   .addClass ('ui-icon-pencil')
                   .appendTo ($('<li>').addClass ('edit')
                                       .addClass ('ui-state-default')
                                       .addClass ('ui-corner-all')
                                       .attr ('title', 'Edit')
                                       .appendTo (ul));*/
        $('<span>').addClass ('ui-icon')
                   .addClass ('ui-icon-trash')
                   .appendTo ($('<li>').addClass ('doc-trash')
                                       .addClass ('ui-state-default')
                                       .addClass ('ui-corner-all')
                                       .attr ('title', 'Delete')
                                       .appendTo (ul));
        li.appendTo ($("#" + ulID));
    });
    
    // Refresh
    $("#" + ulID).sortable ('refresh');
    setIconHover ();
    bindIconEvents ();
}

/**
 * Render the grid of sortable image resources.
 * 
 * @param ulID {String} DOM ID for UL to render ordering in
 * @param data {Array<Object>} Image objects to render
 * @param baseURL {String} Path to prepend to image repository path
 */
function renderImageSortable (ulID, data, baseURL) {
    // Clear the set
    $("#" + ulID).find ('li').remove ();
    
    // Iterate and build the group
    $.each (data, function (id, obj) {
        var li = $('<li>').addClass ('ui-state-default');
        var div = $('<div>').css ('vertical-align', 'center')
                            .css ('margin-left', 'auto')
                            .css ('margin-right', 'auto')
                            .appendTo (li);
        $('<span>').addClass ('sortable-hidden-id')
                   .addClass ('hidden')
                   .html (id)
                   .appendTo (div);
        $('<img>').attr ('src', baseURL + obj.path)
                  .attr ('alt', obj.title)
                  .css ('width', obj.thumb_width)
                  .css ('height', obj.thumb_height)
                  .appendTo ($('<div>').addClass ('img')
                                         .appendTo (div));
        $('<div>').addClass ('caption')
                  .html (obj.desc)
                  .appendTo (div);
        var divDel = $('<div>').addClass ('op-icons')
                               .appendTo (div);
        var spn = $('<span>').addClass ('image-trash-wrapper')
                             .addClass ('ui-widget')
                             .addClass ('ui-helper-clearfix')
                             .appendTo (divDel);
        var spnInner = $('<span>').addClass ('image-trash')
                                  .addClass ('ui-state-default')
                                  .addClass ('ui-corner-all')
                                  .appendTo (spn);
        $('<span>').addClass ('ui-icon')
                   .addClass ('ui-icon-trash')
                   .appendTo (spnInner);
        /*var ul = $('<ul>').addClass ('icons-edit-buttons')
                          .addClass ('ui-widget')
                          .addClass ('ui-helper-clearfix')
                          .appendTo (spn);
        $('<span>').addClass ('ui-icon')
                     .addClass ('ui-icon-pencil')
                     .appendTo ($('<li>').addClass ('edit')
                                         .addClass ('ui-state-default')
                                         .addClass ('ui-corner-all')
                                         .attr ('title', 'Edit')
                                         .appendTo (ul));
        $('<span>').addClass ('ui-icon')
                   .addClass ('ui-icon-trash')
                   .appendTo ($('<li>').addClass ('image-trash')
                                       .addClass ('ui-state-default')
                                       .addClass ('ui-corner-all')
                                       .attr ('title', 'Delete')
                                       .appendTo (ul));*/
        li.appendTo ($("#" + ulID));
    });
    
    // Refresh
    $("#" + ulID).sortable ('refresh');
    setIconHover ();
    bindIconEvents ();
}

/**
 * Save static page content.
 *
 * @param txaID {String} DOM ID for editor field with content
 * @param contentType {String} Type of content being saved
 * @param spnID {String} DOM ID for element to show success/error
 */
function saveContentPage (txaID, contentType, spnID) {
    $.ajax ({
        url: 'ajax/saveContent.php',
        method: 'POST',
        data: {
            type: contentType,
            html: $("#" + txaID).val ()
        },
        dataType: 'json',
        success: function (data) {
            if (data.success) {
                showSuccess ("#" + spnID);
            }
            else {
                alert ('An unknown error has occurred.');
                showError ("#" + spnID);
            }
        },
        error: function () {
            alert ('An unknown error has occurred.');
            showError ("#" + spnID);
        }
    });
}

/**
 * Set events to handle icon hovering correctly.
 */
function setIconHover () {
    $(".icons-edit-buttons li").mouseenter (function () { $(this).addClass ("ui-state-hover"); })
                               .mouseleave (function () { $(this).removeClass ("ui-state-hover"); });
    $(".op-icons .image-trash").mouseenter (function () { $(this).addClass ("ui-state-hover"); })
                               .mouseleave (function () { $(this).removeClass ("ui-state-hover"); });
}

/**
 * Bind events for handling icon events (clicks).
 */
function bindIconEvents () {
    $(".doc-trash").off ('click');
    $(".doc-trash").on ('click', function (event) {
        var hold_scope = $(this);
        $.ajax ({
            url: 'ajax/deleteResource.php',
            method: 'POST',
            data: {
                id: $(this).parent ().parent ().parent ().find ('.sortable-hidden-id').first ().html ()
            },
            dataType: 'json',
            success: function (data) {
                if (data.success) {
                    var id = hold_scope.parent ().parent ().closest ('ul').attr ('id');
                    hold_scope.parent ().parent ().parent ().remove ();
                    $("#" + id).sortable ('refresh');
                }
                else {
                    alert ('A MySQL error occurred: ' + data.err_msg);
                }
            },
            error: function () {
                alert ('An unknown error has occurred.');
            }
        });
    });
    
    $(".image-trash").off ('click');
    $(".image-trash").on ('click', function (event) {
        var hold_scope = $(this);
        $.ajax ({
            url: 'ajax/deleteResource.php',
            method: 'POST',
            data: {
                id: $(this).parent ().parent ().parent ().find ('.sortable-hidden-id').first ().html ()
            },
            dataType: 'json',
            success: function (data) {
                if (data.success) {
                    var id = hold_scope.parent ().parent ().parent ().closest ('ul').attr ('id');
                    hold_scope.parent ().parent ().parent ().parent ().remove ();
                    $("#" + id).sortable ('refresh');
                }
                else {
                    alert ('A MySQL error occurred: ' + data.err_msg);
                }
            },
            error: function () {
                alert ('An unknown error has occurred.');
            }
        });
    });
}

/**
 * Handle the click event for deleting a website link.
 *
 * @param li (jQuery object) List element that was clicked to delete
 */
function deleteLink (li) {
	var link_id = $(li).find(".sortable-hidden-id").html();
	$.ajax ({
		url: 'ajax/deleteLink.php',
		method: 'POST',
		data: {
			id: link_id
		},
		dataType: 'json',
		success: function(data) {
			if (data.success) {
				// Remove the entry and refresh the list
				$(li).remove();
				$("#ulLinkSort").sortable ('refresh');
			}
			else {
				alert ('Unable to delete link.\r\nMySQL Error Message: ' +
				       data.err_msg);
			}
		},
		error: function () {
			alert ('An unknown error has occurred.');
		}
	});
}

/**
 * Show the success indicator (check mark) inside a DOM element.
 *
 * @param selector {String} jQuery selector for DOM element to show indicator
 *                          within
 */
function showSuccess (selector) {
    $(selector).css ('display', 'inline-block');
    $(selector).removeClass ('inputError');
    $(selector).addClass ('inputValid');
    timeOutResult (selector);
}

/**
 * Show the error indicator (red x) inside a DOM element.
 *
 * @param selector {String} jQuery selector for DOM element to show indicator
 *                          within
 */
function showError (selector) {
    $(selector).css ('display', 'inline-block');
    $(selector).removeClass ('inputValid');
    $(selector).addClass ('inputError');
    timeOutResult (selector);
}

/**
 * Time out the result indicator (success or error).
 *
 * @param selector {String} jQuery selector for DOM element to clear
 */
function timeOutResult (selector) {
    setTimeout (function () {
        $(selector).fadeOut (600, function () {
            $(selector).removeClass ('inputValid');
            $(selector).removeClass ('inputError');
        });
    }, 3000);
}