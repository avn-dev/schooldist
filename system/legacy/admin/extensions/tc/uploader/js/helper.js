var UploaderHelper = Class.create({ 
    
    aUploaders: [],
    oErrorHandler: null,
    oFormHandler: null,
    
    initializeAllUploader: function (oErrorHandler, oFormHandler) {
        
        for(var i = 0; i < this.aUploaders.length; i++){
            var oUploader = this.aUploaders[i];
            if(oUploader){
                oUploader.remove();
                this.aUploaders[i] = null;
            }
        }
        
        $j('.uploader').each(function(iIndex, oDiv){
            if(oDiv){
                oDiv.innerHTML = '';
                this.loadUploader(oDiv);
            }
        }.bind(this));
        
        this.oErrorHandler  = oErrorHandler;
        this.oFormHandler   = oFormHandler;
        
    },
        
    removeUploader: function(sUID){

        for(var i = 0; i < this.aUploaders.length; i++){
            if(oUploader){
                var oUploader = this.aUploaders[i];
                if(oUploader.getUID() == sUID){
                    oUploader.remove(true);
                    this.aUploaders[i] = null;
                }
            }
        }
    },

    loadUploader: function(oDiv){
    
        var iNamespace = oDiv.getAttribute("data-id");
		var iSelected = oDiv.getAttribute("data-selected");
        var sNamespace = oDiv.getAttribute("data-namespace");

        var vFD = new FormData();    
            vFD.append('task', 'init_uploader');     
            vFD.append('namespace', sNamespace);    
            vFD.append('namespace_id', iNamespace);    
			vFD.append('selected_id', iSelected);  
            vFD.append('multiple', 1);   
            vFD.append('drop', 1);    

        var oHandler = function(e){
            this.loadUploaderFinish(e, oDiv);
        }.bind(this);

        var oXHR = new XMLHttpRequest();
            oXHR.addEventListener('load', oHandler , false);
            oXHR.open('POST', '/system/extensions/tc/uploader/request.php');
            oXHR.send(vFD);
    },
        
    loadUploaderFinish: function(e, oDiv){
        if(e.target.responseText){
            oDiv.innerHTML = e.target.responseText ;
            var oForm = oDiv.childNodes[0];
            var oUploader = new Uploader();
            if(this.oErrorHandler){
                oUploader.setErrorHandler(this.oErrorHandler);
            }
            if(this.oFormHandler){
                oUploader.setFormHandler(this.oFormHandler);
            }
            oUploader.attachToForm(oForm);
            this.aUploaders[this.aUploaders.length] = oUploader;
        } else {
            oDiv.id = 'tc_upload_error_'+(Math.floor(Math.random() * 6) + 1);
            this.oErrorHandler(['unable_to_load_uploader'], oDiv);
        }
        
    }

});


