<link rel="stylesheet" href="/system/extensions/tc/upload/css/uploader.css" ></link>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript">
$j = $.noConflict();
// Code that uses other library's $ can follow here.
</script>
<script src="//ajax.googleapis.com/ajax/libs/prototype/1.7.1.0/prototype.js"></script>
<script src="/system/extensions/tc/upload/js/uploader.js"></script>
<script src="/system/extensions/tc/upload/js/helper.js"></script>
<script>    
$j(function(){

    var oUploaderHelper = new UploaderHelper();
    oUploaderHelper.initializeAllUploader();

});
</script>

<div class="uploader" data-namespace="dummy" data-id="1"></div>
    
    