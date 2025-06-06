const ajaxurlFront = dynamicConten.ajaxurl;
const checkingText = dynamicConten.loadingText;
const nonce = dynamicConten.nonce;
jQuery(document).ready(function($){
    // Function to set cookie
    function setCookie(name, value, hours) {
        let d = new Date();
        d.setTime(d.getTime() + (hours * 60 * 60 * 1000)); // Expiry time in milliseconds
        document.cookie = name + "=" + value + ";path=/;expires=" + d.toUTCString();
    }

    // Function to get cookie
    function getCookie(name) {
        let match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    }

    // Check if a tab was previously active
    let activeTab = getCookie('activeTab');
    console.log("checkTab:" + activeTab)


    var $div = $('#dataTabs_organizations');
    var $table = $div.find('table.adminTable');

    if((activeTab == "organizations" && !$table.length) || activeTab == "deals"){
        $("p.submit").hide();
    }
    else{
        $("p.submit").show(); 
    }
    if((activeTab == "organizations")){
        $("p.manageOrgBtn").show();
    }
    else{
        $("p.manageOrgBtn").hide();
    }

    if (activeTab) {
        $('.dataTabs li a').removeClass('active');
        $('.dataTabsContent').hide();
        $('#dataTabs_' + activeTab).show();
        $('.dataTabs li a[data-id="' + activeTab + '"]').addClass('active');
    } else {
        $('.dataTabsContent').first().show();
        $('.dataTabs li a').first().addClass('active');
    }



    // Handle tab clicks
    $('.dataTabs li a').on('click', function(){
        console.log('tab changed')
        let theElm = $(this);
        let theID = theElm.data('id');

        console.log("activeID" + theID);
        
        $('.dataTabsContent').hide();
        $('#dataTabs_' + theID).show();
        $('.dataTabs li a').removeClass('active');
        theElm.addClass('active');

        // Save the active tab in cookies (expires in 24 hours)
        setCookie('activeTab', theID, 24);

        if((theID == "organizations" && !$table.length) || theID == "deals"){
            $("p.submit").hide();
        }else{
            $("p.submit").show(); 
        }
        if((theID == "organizations")){
            $("p.manageOrgBtn").show();
        }else{
            $("p.manageOrgBtn").hide();
        }

    });
    $('.activityToggle').on('click', function(){
        let theElm = $(this);
        let theID = theElm.data('id');
        $('.activityContainer').hide();
        $('#' + theID).show();
        $('.activityToggle').removeClass('active');
        theElm.addClass('active');
    });
    // add/remove organization
    $(".modify-organization").on("click", function() {
        var button = $(this);
        var Text = button.text()
        var orgID = button.data("org-id");
        var personID = button.data("person-id");
        var targetTD = button.closest('td')
        button.text('Loading...')
        $.ajax({
            url: ajaxurlFront,
            type: "POST",
            data: {
                action: "modifyOrganization",
                org_id: orgID,
                person_id: personID
            },
            beforeSend: function() {
                $("#action-buttons-" + orgID).html( checkingText+"...");
            },
            success: function(response) {
                var status = response.data.status;
                if (response.success) {
                    let exist = false;
                    if(status == 'exists'){
                        exist = true;
                    }
                    var actionText = exist ? "Remove" : "Add";
                    var actionClass = exist ? "remove-person" : "add-person";                  
                    
                    var actionButton = `<div class="responseMessage">${response.data.message} :</div> <button class='${actionClass}' data-org-id='${orgID}' data-person-id='${personID}'>${actionText}</button>`;

                    targetTD.html(actionButton);
                } else if(response.data.message) {
                    alert(response.data.message);
                    button.text(Text)
                }else{
                    alert("Action failed!");
                    button.text(Text)
                }
            }
        });
    });

    $(document).on("click", ".add-person, .remove-person", function() {
        var button = $(this);
        var Text = button.text()
        var orgID = button.data("org-id");
        var personID = button.data("person-id");
        var action = button.hasClass("add-person") ? "add" : "remove";
        button.text('Loading')
        $.ajax({
            url: ajaxurlFront,
            type: "POST",
            data: {
                action: "modifyPersonOrganization",
                org_id: orgID,
                person_id: personID,
                modify_action: action
            },
            beforeSend: function() {
                button.prop("disabled", true).text("Processing...");
            },
            success: function(response) {
                if (response.success) {
                    var newActionText = action === "add" ? "Remove" : "Add";
                    var newActionClass = action === "add" ? "remove-person" : "add-person";
                    button.closest('td').find('.responseMessage').html(response.data.message)
                    button.prop("disabled", false).removeClass("add-person remove-person").addClass(newActionClass).text(newActionText);
                } else if(response.data.message) {
                    alert(response.data.message);
                    button.text(Text)
                }else{
                    alert("Action failed!");
                    button.text(Text)
                }
            }
        });
    });
});


jQuery(document).ready(function($) {
    setTimeout(function() {
        $('.pgfc-success-message').fadeOut(500, function() {
        $(this).remove();
        });
    }, 2000); // 2 seconds
});
jQuery(document).ready(function($) {
    $('.pgfc-readonly').each(function() {
        $(this).find('input[type="radio"], input[type="url"], textarea').each(function() {
            $(this).prop('readonly', true);
        });
         $(this).find('input[type="checkbox"]').each(function() {
            $(this).prop('disabled', true);
        });

    });
    $('.pgfc-readonly select').attr('readonly' , true);
});


jQuery(document).ready(function($) {
    let delayTimer;

    $('.userEmail input').on('input', function() {
        clearTimeout(delayTimer);
        const email = $(this).val();
        // Basic validation
         $(".userEmail .ginput_container .login-message").remove()
        if (!email || !email.includes('@')) return;
           
            delayTimer = setTimeout(function() {
            $.ajax({
                url: ajaxurlFront,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'check_or_create_user_by_email',
                    current_url: window.location.href,
                    email: email
                },
                success: function(response) {
                    if(!response.success){
                        $(".gform_button").css({
                        'opacity': '0.5',
                        'pointer-events': 'none'
                        });
                    }
                    if(response.success){
                        $(".gform_button").css({
                        'opacity': '',
                        'pointer-events': ''
                        });
                    }
                    if(response.data.message){
                        $('.userEmail .ginput_container').append(
                            '<div class="login-message gfield_validation_message" style="margin-top:5px;">' + response.data.message + '</div>'
                        );
                    }
                   
                },
                error: function(err) {
                    console.error('Error checking/creating user:', err);
                }
            });
        }, 600); // delay to prevent spamming the server
    });
});



jQuery(document).ready(function($) {
    const $input = $('.organizationName input');
    let timeout;
    let orgMatched = false; // default
    $input.on('input', function () {
        $('.organizationField').show();
        const query = $(this).val();
        if (query.length >= 2) {
            clearTimeout(timeout);
            timeout = setTimeout(function () {
                $('.org-suggestions, .org-loader').remove();
                $input.after('<div class="org-loader">'+dynamicConten.searchingText+'...</div>');
                $.ajax({
                    url: ajaxurlFront, // Provided by WordPress in admin or manually define
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'search_organizations_by_name',
                        term: query
                    },
                    success: function(response) {
                        $('.org-loader').remove();
                        $('.org-suggestions, .org-suggestions-noresult').remove(); // remove old suggestions and messages
                        orgMatched = false; 
                        if (response.success && response.data.length > 0) {
                            let suggestionHTML = '<ul class="org-suggestions">';
                            const inputVal = $input.val().trim().toLowerCase();
                            response.data.forEach(function(org) {
                                if (org.name.trim().toLowerCase() === inputVal) {
                                    orgMatched = true;
                                }
                                suggestionHTML += `<li data-id="${org.id}">${org.name}</li>`;
                            });
                            suggestionHTML += '</ul>';
                            $input.after(suggestionHTML);
                        }else{
                            const message = `<div class="org-suggestions-noresult no-results gfield_validation_message">${dynamicConten.orgNotFound}</div>`;
                            $input.after(message);
                        }
                    },
                    error: function () {
                        $('.org-loader').remove(); // Also remove loader on error
                    }
                });
            }, 300); // debounce
        } else {
                $('.org-suggestions, .org-loader').remove();
        }
    });
    // Optional: fill input on suggestion click
    let suggestionClicked = false;
    $(document).on('click', '.org-suggestions li', function () {
        suggestionClicked = true;
        const $clickedSuggestion = $(this);
        const $input = $clickedSuggestion.closest('.organizationField').find('input'); // Assuming there's an input inside
        $input.val($clickedSuggestion.text());
        $('.org-suggestions , .org-suggestions-noresult').remove();
        $('.organizationField').each(function () {
            console.log('**');
            const $orgField = $(this);
            const isPersonField = $orgField.hasClass('personField');
            if ($orgField.hasClass('organizationName')) {
                console.log('org');
                $orgField.show();
                return;
            }
            if (!isPersonField) {
                console.log('Hiding organization field');
                $orgField.hide();
            }
        });
    });
    $input.on('focusout', function () {
        console.log('orgMatched:' + orgMatched);
        setTimeout(function () {
            $('.org-suggestions, .org-loader').remove();
           if (!suggestionClicked) {
                 $('.org-suggestions-noresult').remove();
                const message = `<div class="org-suggestions-noresult no-results gfield_validation_message">${dynamicConten.orgNotFound}</div>`;
                $input.after(message);
            }
            if(orgMatched){
                 $('.org-suggestions-noresult').remove();
            }
        }, 100); // delay allows click to register before cleanup
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.organizationName').length) {
            $('.org-suggestions').remove();
        }
    });
    
});
