// let pluginSlugname = pgfc_admin_data.pgfc_plugin_slug;
// jQuery('tr[data-slug="'+pluginSlugname+'"] .update-message').hide();
// var viewHrefVersion = jQuery("#"+pluginSlugname+"-update a").attr("href");
// jQuery(".pgfc-view-details").attr('href' , viewHrefVersion)

function pgfctoggleCustomFun(elm){
    jQuery(elm).toggle()
}
jQuery(document).ready(function($) {
    let lastIndex = $('.pgfcItem').length - 1;
    var currentIndex = 0;
    if(lastIndex >= 0){
      currentIndex = lastIndex;
    }
    function pgfc_addNewItem() {
      currentIndex++; // Increment the item index
      var newItem = $('.pgfcItem:first').clone(); // Clone the first pgfcItem      
      newItem.find('td:last select').remove();
      newItem.find('input, select').val('');
      newItem.find('input, select').attr('name', function(index, attr) {
        return attr.replace(/\[0\]/g, '[' + currentIndex + ']');
      }); 
      newItem.insertAfter('.pgfcItem:last');
      $('.pgfc_removepgfcItem').toggle(currentIndex > 0);
    }
    function pgfc_removeLastItem() {
      if (currentIndex > 0) {
        $('.pgfcItem:last').remove();
        currentIndex--;
        $('.pgfc_removepgfcItem').toggle(currentIndex > 0);
      }
    }
    $('.pgfc_addItem').on('click', function() {
        pgfc_addNewItem();
    });
    $('.pgfc_removepgfcItem').on('click', function() {
        pgfc_removeLastItem();
    });

    $(document).on('change', '.onChangeFun', function () {
        addAttributeDropDown($(this))
      
    });  
    $(document).on('click', '.removeMapping', function(){
      let entryRow = $(this).closest('tr');
      entryRow.remove()
      reIndexRows()
      //console.log(index)
    })
    function reIndexRows() {
      $('.pgfcItem').each(function(index) {
          $(this).find('input, select, textarea').each(function() {
              let nameAttr = $(this).attr('name');
              if (nameAttr) {
                  let updatedName = nameAttr.replace(/\[.*?\]/, '[' + index + ']'); 
                  $(this).attr('name', updatedName);
              }
          });
      });       
    }

    // add/remove organization
    $(".modify-organization").on("click", function() {
        var button = $(this);
        var Text = button.text()
        var orgID = button.data("org-id");
        var personID = button.data("person-id");
        var targetTD = button.closest('td')
        button.text('Loading...')
        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: {
                action: "modifyOrganization",
                org_id: orgID,
                person_id: personID
            },
            beforeSend: function() {
                $("#action-buttons-" + orgID).html("Checking...");
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
            url: ajaxurl,
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
    function addAttributeDropDown(theElm){
        addIndexField(theElm)
        let theSlug = theElm.data('slug');
          let theID = theElm.val();
      
          let $thisTr = theElm.closest('tr');
          let $cloneSource = $('#' + theSlug + '_' + theID);
            if(!theID){
                $('.'+theSlug).html('')
                return
            }
          if ($thisTr.length) {
              // If inside a tr, run your original logic
              let index = $thisTr.index();
              let $element = $cloneSource.clone(true).removeAttr('id');
              $element.attr('name', $element.attr('name').replace(/\[\d+\]/, '[' + index + ']'));
              $element.val('')
              $('.' + theSlug).eq(index).html($element);
          } else {
              // If NOT inside a tr, loop through all theSlug instances
              $('.' + theSlug).each(function () {
                  let $target = $(this);
                  let index = $target.closest('tr').index();
      
                  let $element = $cloneSource.clone(true).removeAttr('id');
                  $element.attr('name', $element.attr('name').replace(/\[\d+\]/, '[' + index + ']'));
                  $element.val('')
                  $target.html($element);
              });
          }
    }
    function addIndexField(theElm){
        let theID = theElm.val();
        if(!theID){
            theElm.closest('td').find('.apiActivityIndex').remove()
            return
        }
        let isActivity = theElm.val() === 'activity';
        let originalName = theElm.attr('name') || '';
        let newName = originalName.replace(/\[apiLabel\](?!.*\[apiLabel\])/, '[apiLabelIndex]');
        let ifExist = theElm.closest('td').find('.apiActivityIndex').length
        console.log(ifExist)
        if(isActivity){
            if(!ifExist){
                theElm.after('<input name="' + newName + '" type="number" required class="apiActivityIndex" placeholder="Add array key" />');
            }
        }else{
            theElm.closest('td').find('.apiActivityIndex').remove()
        }
    }
});

