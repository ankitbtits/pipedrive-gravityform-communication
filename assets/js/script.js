const ajaxurlFront = dynamicConten.ajaxurl;
const checkingText = dynamicConten.loadingText;
console.log(checkingText)
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
        $(this).find('input[type="checkbox"], input[type="radio"], input[type="url"], input[type="text"], textarea, select').each(function() {
            $(this).prop('disabled', true);
        });
    });
});