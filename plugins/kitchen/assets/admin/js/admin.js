/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


jQuery(document).ready(function () {
    jQuery('.tabs .tab-links a').on('click', function (e) {
        var currentAttrValue = jQuery(this).attr('href');
        // Show/Hide Tabs
        jQuery('.tabs ' + currentAttrValue).show().siblings().hide();
        // Change/remove current tab to active
        jQuery(this).parent('li').addClass('active').siblings().removeClass('active');
        e.preventDefault();
    });
    jQuery(document).on('click', '#kitchen_sync_btn, #kitchen_delete_btn, #pack_sync_btn, #kitchen_sync_btn, #product_sync_btn, #sync_all_btn', function (e) {
        e.preventDefault();
        var packID = jQuery(this).data('packid');
        var action = jQuery(this).data('action');
        var nonce = jQuery(this).data('nonce');
        var post_id = jQuery(this).data('postid');
        ajaxcall(nonce, action, packID, post_id);
    })

    function ajaxcall(nonce, action, packID, post_id) {
        jQuery('div').remove('.notice');
        jQuery('#loading').removeClass("hidden");
        jQuery.ajax({
            type: "post",
            url: ajaxurl,
            dataType: 'json',
            data: {nonce: nonce, action: action, packID: packID, post_id: post_id},
            success: function (response) {
                
                if(jQuery.isEmptyObject(response.data)){
                    jQuery('#kitchen_list_tabcontent').prepend('<div class="notice notice-success is-dismissible"><p>' + response + '</p></div>');
                    jQuery('.wrap').prepend('<div class="notice notice-success is-dismissible"><p>' + response + '</p></div>');
                } else {
                    jQuery('#kitchen_list_tabcontent').prepend('<div class="notice notice-error is-dismissible"><p>' + response.data + '</p></div>');
                    jQuery('.wrap').prepend('<div class="notice notice-error is-dismissible"><p>' + response.data + '</p></div>');

                }

            },
            error: function (xhr, ajaxOptions, thrownError) {
                
                jQuery('div').remove('.notice');
                jQuery('#kitchen_list_tabcontent').prepend('<div class="notice notice-error is-dismissible"><p>' + thrownError + '. Please try again</p></div>');
                jQuery('.wrap').prepend('<div class="notice notice-error is-dismissible"><p>' + thrownError + '. Please try again</p></div>');

            },
            complete: function () {
                if (action === 'delete_kitchen_listing') {
                  //  location.reload();
                }
                jQuery('#loading').addClass("hidden");
            }
        });
    }
});