var Uploader  = Class.create({ 

    oDiv: null,
    oReader: null,
    oErrorHandler: null,
    oFormHandler: null,
    aFiles: [],
    aFileTypes: [],
    aErrors: [],
    oErrorMessages: {},

    // common variables
    iBytesUploaded: 0,
    iBytesTotal: 0,
    iPreviousBytesLoaded: 0,
    iMaxFilesize: null, //1048576, // 1MB
    iLoaderWidth: 0,

    setErrorMessages: function(oMessages){
        this.oErrorMessages = oMessages;
    },
        
    setErrorHandler: function(oHandler){
        this.oErrorHandler = oHandler;
    },

    setFormHandler: function(oHandler){
        this.oDivHandler = oHandler;
    },

    /**
     * Hängt den Uploader an ein Forumular
     * 
     * ACHTUNG!
     * Muste das auf DIV umstellen da verschachtelte Forms in Chrome nicht gehen
     * und nach dem html standard auch nicht erlaubt sind!
     * 
     * @param {object} oForm
     * @returns {undefined}
     */
    attachToForm: function(oForm){

        this.oDiv  = oForm;
        var sId     = this.oDiv.id;

        $j('#'+sId+' .start_btn').bind('click', function() {
            this.startUploading();
        }.bind(this));
    
        $j('#'+sId+' .file_input').bind('change', function() {
            this.fileSelected();
        }.bind(this));
    
        $j('#'+sId+' .delete_files_btn').bind('click', function() {
            this.removeFiles();
        }.bind(this));
        
        
        $j('#'+sId+' .add_files_btn').click(
            function(e){
                e.preventDefault();
                $j('#'+sId+' .file_input').click();
            }
        );
        
        var oFileSize   = this.getUploadElement('max_file_size')
        var oFileTypes  = this.getUploadElement('file_types')
        var sFileTypes  = oFileTypes.value;
        var aFileTypes  = sFileTypes.split(',');

        if (oFileSize) {
            this.setMaximumFileSizeInBytes(oFileSize.value);
        }
        this.setFileTypes(aFileTypes);
        
        var oLoader = this.getUploadElement('progress');
        this.iLoaderWidth = oLoader.style.width;
        
        this.prepareDrop(this.oDiv);
        this.prepareDrag(this.oDiv);
        
        
        var sFiles = this.getUploadElement('current_files').value;
        var aFiles = sFiles.split(',');
        if(aFiles && aFiles.length > 0){
            for(var i = 0; i < aFiles.length; i++){
                if(aFiles[i] && aFiles[i] != ""){
                    this.addPreviewImage('/'+aFiles[i], '', '/'+aFiles[i]);
                }
            }
        }
    },
    /**
     * gibt die eindeutige ID zurück
     */
    getUID: function(){
        if(this.oDiv){
            return this.oDiv.id;
        } else {
            return null;
        }
    },
        
    removeFiles: function(){
        if(this.oDiv){
            var vFD = new FormData();   
                this.appendUploaderDataToFormRequest(vFD);
                vFD.append('tc_upload_id', this.getUID()); 
                vFD.append('task', 'remove_files'); 
            // create XMLHttpRequest object, adding few event listeners, and POSTing our data
            var oXHR = new XMLHttpRequest();
            oXHR.addEventListener('load', this.uploadFinish.bind(this), false);
            oXHR.open('POST', this.oDiv.getAttribute('data-action'));
            oXHR.send(vFD);
            this.reset();
        }
    },
        
    remove: function(bDeleteFiles){
        if(this.oDiv){
            var vFD = new FormData();  
                this.appendUploaderDataToFormRequest(vFD);
                vFD.append('tc_upload_id', this.getUID()); 
                vFD.append('task', 'remove_uploader'); 
                if(bDeleteFiles){
                    vFD.append('delete_files', bDeleteFiles); 
                }

            // create XMLHttpRequest object, adding few event listeners, and POSTing our data
            var oXHR = new XMLHttpRequest();
            oXHR.open('POST', this.oDiv.getAttribute('data-action'));
            oXHR.send(vFD);
        }
    },
        
    appendUploaderDataToFormRequest: function(vFD){
        //vFD.append('task', 'remove_uploader'); 
    },
        
    setFileTypes: function(aTypes){
        this.aFileTypes = aTypes;
    },
        
    setMaximumFileSizeInBytes: function(fBytes){
        this.iMaxFilesize = fBytes;
    },
        
    /**
     * holt ein Element anhand der Klasse vom aktuellen Formular
     * @param {type} sClass
     * @returns {$}
     */
    getUploadElement: function(sClass){
        if(!this.oDiv){
            return null;
        }
        var oElement = $j('#'+this.getUID()+' .'+sClass);
        if(oElement){
            oElement = oElement.get(0);
        }
        return oElement;
    },
        
    reset: function(){
        
        var oUploadResponse = this.getUploadElement('upload_response');
            oUploadResponse.innerHTML = '';
        
        var oDiv        = this.getUploadElement('filename');
            oDiv.innerHTML = '';
            
        this.getUploadElement('bg').style.display = 'block';
        this.getUploadElement('error').style.display = 'none';
            
        this.aFiles = new Array();
    },
        
    prepareReader: function(oFile){

        var oReader = new FileReader();
        oReader.onload = function(e){

            var sImage = this.getFileImage(oFile, e.target.result);
            this.addPreviewImage(sImage, oFile.name, e.target.result);
                    
        }.bind(this);
   
        return oReader;
    },
        
    addPreviewImage: function(sImage, sName, sOriginalSrc){
        var oDiv                = this.getUploadElement('filename');
        var oImage              = this.getUploadElement('preview');

        var oImageCopy  = oImage.cloneNode();
        
        oImageCopy.src      = sImage;
        oImageCopy.title    = sName;
        oImageCopy.alt      = sName;     
        oImageCopy.setAttribute('data-src', sOriginalSrc);
        oImageCopy.onerror = function(){
            this.src = '/admin/extensions/tc/uploader/images/blank.png';
        }
        oImageCopy.onclick  = function(){
            var src = oImageCopy.getAttribute('data-src');
            newwin=window.open(src,'file');
            if (window.focus) {
                newwin.focus()
            }
            return false;
        };
        oImageCopy.show();
        oDiv.appendChild(oImageCopy);

        // we are going to display some custom image information here
        this.getUploadElement('fileinfo').style.display = 'block';
        // we are going to display some custom image information here
        this.getUploadElement('bg').style.display = 'none';
    },
        
   getFileImage: function(oFile, sSrc){
        switch (oFile.type){
            case 'image/jpg':
            case 'image/jpeg':
            case 'image/png':
            case 'image/gif':
            case 'image/bmp':
                return sSrc;
                break;
        }
        return '/admin/extensions/tc/uploader/images/blank.png';
   },
        
    checkFileSize: function(oFile){
        var iSize = oFile.size;
        if(this.iMaxFilesize !== null && iSize > this.iMaxFilesize){
            this.aErrors[this.aErrors.length] = 'wrong_file_size';
        }
    },  
        
    checkFileType: function(oFile){
        return true; // rausgenommen da viele dateien nen falschen header haben und js seitig nicht gut geprüft werden können TODO irgendwann php seitig machen
        /*var sType = oFile.type;
        if(this.aFileTypes.indexOf(sType) == -1){
            this.aErrors[this.aErrors.length] = 'wrong_file_type';
        }*/
    },

    secondsToTime: function (secs) { // we will use this function to convert seconds in normal time format
        var hr = Math.floor(secs / 3600);
        var min = Math.floor((secs - (hr * 3600))/60);
        var sec = Math.floor(secs - (hr * 3600) -  (min * 60));

        if (hr < 10) {hr = "0" + hr; }
        if (min < 10) {min = "0" + min;}
        if (sec < 10) {sec = "0" + sec;}
        if (hr) {hr = "00";}
        return hr + ':' + min + ':' + sec;
    },

    bytesToSize: function (bytes) {
        var sizes = ['Bytes', 'KB', 'MB'];
        if (bytes == 0) return 'n/a';
        var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
        return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + sizes[i];
    },

    fileSelected: function() {

        // hide different warnings
        this.getUploadElement('error2').style.display = 'none';
        this.getUploadElement('abort').style.display = 'none';
        this.getUploadElement('warnsize').style.display = 'none';

        // get selected file element
        this.reset();
        this.aFiles  = this.getUploadElement('file_input').files;
        this.aErrors = new Array();
        
        for(var i = 0; i < this.aFiles.length; i++){
            // read selected file as DataURL
            this.addFileToReader(this.aFiles[i]);
        }
        
    },
        
    addFileToReader:function(oFile){
        this.checkFileSize(oFile);
        this.checkFileType(oFile);
        var oReader = this.prepareReader(oFile);
        oReader.readAsDataURL(oFile);
    },

    startUploading: function () {
        
        if(this.aErrors.length <= 0){
            // cleanup all temp states
            this.iPreviousBytesLoaded = 0;
            this.getUploadElement('error2').style.display = 'none';
            this.getUploadElement('abort').style.display = 'none';
            this.getUploadElement('warnsize').style.display = 'none';
            //this.getUploadElement('progress_percent').innerHTML = '';
            var oProgress = this.getUploadElement('progress');
            oProgress.style.display = 'block';

            // get form data for POSTing
            //var vFD = this.getUploadElement('upload_form').getFormData(); // for FF3
            var vFD = new FormData();  
                this.appendUploaderDataToFormRequest(vFD);  
                vFD.append('tc_upload_id', this.getUID());      
                
            for(var i = 0; i < this.aFiles.length; i++){
               vFD.append('tc_upload_'+this.getUID()+'['+i+']', this.aFiles[i]); 
            }
            
            if(this.oDivHandler){
                this.oDivHandler(vFD);
            }

            // create XMLHttpRequest object, adding few event listeners, and POSTing our data
            var oXHR = new XMLHttpRequest();
            oXHR.upload.addEventListener('progress', this.uploadProgress.bind(this), false);
            oXHR.addEventListener('load', this.uploadFinish.bind(this), false);
            oXHR.addEventListener('error', this.uploadError.bind(this), false);
            oXHR.addEventListener('abort', this.uploadAbort.bind(this), false);
            oXHR.open('POST', this.oDiv.getAttribute('data-action'));
            oXHR.send(vFD);
        } else {
            this.displayErrors();
        }
        
    },
        
    displayErrors: function(){

        if(this.oErrorHandler){
            this.oErrorHandler(this.aErrors, this.oDiv, this);
        } else {
            for(var i = 0; i < this.aErrors.length; i++){
                var sError = this.aErrors[i];
                var sMessage = 'unknown error';
                if(this.oErrorMessages && this.oErrorMessages[sError]){
                    sMessage = this.oErrorMessages[sError];
                }
                this.getUploadElement('error').innerHTML += sMessage+'<br/>';
                this.getUploadElement('error').style.display = 'block';
            }
        }
    },

    uploadProgress: function (e) { // upload process in progress
        if (e.lengthComputable) {
            this.getUploadElement('progress').style.display = '';
        } else {
            this.getUploadElement('progress').innerHTML = 'unable to compute';
        }
    },

    uploadFinish: function (e) { // upload successfully finished
        var oUploadResponse = this.getUploadElement('upload_response');
        oUploadResponse.innerHTML = e.target.responseText;
        //this.getUploadElement('progress_percent').innerHTML = '100%';
        this.getUploadElement('progress').style.display = 'none';
    },

    uploadError: function (e) { // upload error
        this.getUploadElement('error2').style.display = 'block';
    },  

    uploadAbort: function (e) { // upload abort
        this.getUploadElement('abort').style.display = 'block';
    },


    prepareDrop: function (drop){
        this.addEventHandler(drop, 'drop', function (e) {
            e = e || window.event; // get window.event if e argument missing (in IE)   
            if (e.preventDefault) { e.preventDefault(); } // stops the browser from redirecting off to the image.

            var dt          = e.dataTransfer;
            var files       = dt.files;
            
            this.reset();
            this.aFiles     = files;
            this.aErrors    = new Array();
            
            for (var i=0; i<files.length; i++) {
              var file = files[i];
              this.addFileToReader(file);
            }
            return false;
      }.bind(this));
    },

    prepareDrag: function (drop){
        if(window.FileReader) { 
            this.addEventHandler(drop, 'dragover', this.cancel);
            this.addEventHandler(drop, 'dragenter', this.cancel);
        } else { 
            this.getUploadElement('status').innerHTML = 'Your browser does not support the HTML5 FileReader.';
        }
    },

    cancel: function (e) {
        if (e.preventDefault) { e.preventDefault(); }
        return false;
    },

    addEventHandler: function (obj, evt, handler) {
        if(obj.addEventListener) {
            // W3C method
            obj.addEventListener(evt, handler, false);
        } else if(obj.attachEvent) {
            // IE method.
            obj.attachEvent('on'+evt, handler);
        } else {
            // Old school method.
            obj['on'+evt] = handler;
        }
    }
});
