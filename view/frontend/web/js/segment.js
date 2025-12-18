define(['jquery',
        'mage/translate'],
    function ($, $t) {
    'use strict';

    return function () {
        $(document).ready(function(){
            $('#role').on('change', function(){
                loadSuperviorSelect();
                $('#segment_filter').show();
            });
            loadSuperviorSelect();
        });
         function loadSuperviorSelect(){
             var value = $("#role option:selected");
             var isSegment = value.text().toLowerCase();
             if(isSegment.length && isSegment.trim() == "supervisor"){
                 $(".segment_approval").show();
             }else{
                 $(".segment_approval").hide();
             }
        }
    }
});
