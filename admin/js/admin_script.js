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
      let theSlug = $(this).data('slug');
      let theID = $(this).val();
  
      let $thisTr = $(this).closest('tr');
      let $cloneSource = $('#' + theSlug + '_' + theID);
  
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
    });
  
    // $(document).on('change', ".apiAttributeSelect", function(){
    //   let theVal = $(this).val();
    //   let originalName = $(this).attr('name') || '';
    //   let newName = originalName.replace(/\[apiAttribute\](?!.*\[apiAttribute\])/, '[apiAttributeValue]');
    //   if(theVal == 'Other'){
    //     $(this).after('<input name="' + newName + '" class="apiAttributeValue" placeholder="Add field name/key" />');
    //   }else{
    //     $(this).closest('td').find('.apiAttributeValue').remove()
    //   }
    // })

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

    $(document).on('change', '.pipeDriveAPISelect', function(){
      let isActivity = $(this).val() === 'Add an activities';
      let originalName = $(this).attr('name') || '';
      let newName = originalName.replace(/\[apiLabel\](?!.*\[apiLabel\])/, '[apiLabelIndex]');
      if(isActivity){
        $(this).after('<input name="' + newName + '" type="number" required class="apiActivityIndex" placeholder="Add array key" />');
      }else{
        $(this).closest('td').find('.apiActivityIndex').remove()
      }
    });

    // add/remove organization
    $(".modify-organization").on("click", function() {
        var button = $(this);
        var orgID = button.data("org-id");
        var personID = button.data("person-id");

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
                if (response.success) {
                    var actionText = response.data.is_member ? "Remove" : "Add";
                    var actionClass = response.data.is_member ? "remove-person" : "add-person";
                    var actionButton = `<button class='${actionClass}' data-org-id='${orgID}' data-person-id='${personID}'>${actionText}</button>`;

                    $("#action-buttons-" + orgID).html(actionButton);
                } else {
                    $("#action-buttons-" + orgID).html("Error checking status.");
                }
            }
        });
    });

    $(document).on("click", ".add-person, .remove-person", function() {
        var button = $(this);
        var orgID = button.data("org-id");
        var personID = button.data("person-id");
        var action = button.hasClass("add-person") ? "add" : "remove";

        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: {
                action: "modify_person_organization",
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
                    button.prop("disabled", false).removeClass("add-person remove-person").addClass(newActionClass).text(newActionText);
                } else {
                    alert("Action failed!");
                    button.prop("disabled", false);
                }
            }
        });
    });
});
