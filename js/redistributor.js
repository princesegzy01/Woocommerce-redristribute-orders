 (function ($) {    
 	// Remove Profile, Products and Woocommerce menus
	jQuery("#menu-users, #menu-posts-product, #toplevel_page_woocommerce").remove();
	jQuery("#your-profile").html("<br/><h3>Click on Redistributor Orders to view and manage your DrugStoc Orders.</h3><br/>");

 	// Handle stock options 
	jQuery("._case").click(function(){  
		if(jQuery(this).is(":checked")){
			jQuery(this).css("border-color","green").prop("title","In stock");
			jQuery(this).parent().css("background-color","green");
		}else{
			jQuery(this).css("border-color","red").prop("title","Out of stock");
			jQuery(this).parent().css("background-color","red");
		}
	});    

 	// Apply datatable
	jQuery('.redistributor, #ds_redistributor').dataTable({ 
	    "bSort": true,
	    "bPaginate": true,
	    "bLengthChange": true,
	    "bFilter": true,
	    "bInfo": true,
	    "bAutoWidth": true, 
	    "sDom": 'T<"panel-menu dt-panelmenu"lfr><"clearfix">tip',
	    "oTableTools": {
    	  "sSwfPath": "<a href='//cdnjs.cloudflare.com/ajax/libs/datatables-tabletools/2.1.5/swf/copy_csv_xls_pdf.swf' target='_blank' rel='nofollow'>http://cdnjs.cloudflare.com/ajax/libs/datatables-tabletools/2.1.5/swf/copy_csv_xls_pdf.swf</a>",
	      // "sSwfPath": "http://cdnjs.cloudflare.com/ajax/libs/datatables-tabletools/2.1.5/swf/copy_csv_xls_pdf.swf",
	      "aButtons": [ 
	          "csv",
	          "xls",
	          {
	              "sExtends": "pdf",
	              "bFooter": true,
	              "sPdfMessage": "List of All Orders ",
	              "sPdfOrientation": "potriat"
	          },
	          "print"
	    ]}
	});   
}(jQuery)); //end document.ready
 