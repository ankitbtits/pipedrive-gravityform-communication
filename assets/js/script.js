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
        let theElm = $(this);
        let theID = theElm.data('id');

        $('.dataTabsContent').hide();
        $('#dataTabs_' + theID).show();
        $('.dataTabs li a').removeClass('active');
        theElm.addClass('active');

        // Save the active tab in cookies (expires in 24 hours)
        setCookie('activeTab', theID, 24);
    });
});
