jQuery(function($){
    $(document).ready(function(){

        function sendRequest(noticeId, dismissType, customTime){
            $.post(ajaxurl,{
                action: 'sirv_dismiss_notice',
                _ajax_nonce: sirv_dismiss_ajax_object.ajaxnonce,
                notice_id : noticeId,
                dismiss_type: dismissType,
                custom_time: customTime,
            }).done(function(response){
                //debug
                //console.log(response);

            }).fail(function(jqXHR, status, error){
                console.error("Error during ajax request: " + error);
            });
        }

        $('.notice-dismiss').on('click', function(){
            const noticeId = $(this).closest('.sirv-admin-notice').attr('data-sirv-notice-id');
            const dismissType = $(this).closest('.sirv-admin-notice').attr('data-sirv-dismiss-type');
            const customTime = $(this).closest('.sirv-admin-notice').attr('data-sirv-custom-time') || 0;

            if(!!noticeId){
                sendRequest(noticeId, dismissType, customTime);
            }
        });


        $(".sirv-plugin-issues-noticed").on("click", dontShowAgain);
        function dontShowAgain(){
            const noticeId = $(this).attr("data-sirv-notice-id");
            const dismissType = $(this).attr("data-sirv-dismiss-type");


            $('div[data-sirv-notice-id="sirv-conflict-plugins"]').remove();

            sendRequest(noticeId, dismissType, 0);
        }
    }); //domready end
});
