$(document).ready(function () {

    if ($('.icheck input').length > 0) {
        $('.icheck input').iCheck({
            checkboxClass: 'icheckbox_flat-blue',
            radioClass: 'iradio_flat-blue',
            increaseArea: '20%' // optional
        });
    }
    if ($('textarea').length > 0) {
        $('textarea').summernote({
            height: 200
        });
    }
    if ($('select.select2').length > 0) {
        var options = {};
        var select2 = $('select.select2');
        if(select2.data('tags')){
            options.tags = select2.data('tags');
        }
        $('select.select2').select2(options);
    }

    $('[data-toggle=tooltip]').tooltip();

    $('.main-sidebar .sidebar').slimScroll({
        position: 'right',
        height: '92vh',
        color: '#fff',
        railVisible: true,
    });

    setInterval(function(){
        checkNewOrder();
    },2000);
    $(".redirect_me_to_given_url").on('click',function(){
        var url =  $(this).attr("data-href");
        window.location.href = url;
    });

})

function render(props) {
    return function (tok, i) {
        return (i % 2) ? props[tok] : tok;
    };
}

function dzComplete(_this, file, mockFile = '', mediaMockFile = '') {
    if (mockFile !== '') {
        _this.removeFile(mockFile);
        mockFile = '';
    }
    if (mediaMockFile !== '' && _this.element.id === mediaMockFile.collection_name) {
        _this.removeFile(mediaMockFile);
        mediaMockFile = '';
    }
    if (file._removeLink) {
        file._removeLink.textContent = _this.options.dictRemoveFile;
    }
    if (file.previewElement) {
        return file.previewElement.classList.add("dz-complete");
    }
}

function dzRemoveFile(file, mockFile = '', existRemoveUrl = '', collection, modelId, newRemoveUrl, csrf) {
    if (file.previewElement != null && file.previewElement.parentNode != null) {
        file.previewElement.parentNode.removeChild(file.previewElement);
    }
    //if(file.status === 'success'){
    if (mockFile !== '') {
        mockFile = '';
        $.post(existRemoveUrl,
            {
                _token: csrf,
                id: modelId,
                collection: collection,
            });
    } /*else {
        $.post(newRemoveUrl,
            {
                _token: csrf,
                uuid: file.upload.uuid
            });
    }*/
    //}
}

function dzSending(_this, file, formData, csrf) {
    _this.element.children[0].value = file.upload.uuid;
    formData.append('_token', csrf);
    formData.append('field', _this.element.dataset.field);
    formData.append('uuid', file.upload.uuid);
}

function dzMaxfile(_this, file) {
    _this.removeAllFiles();
    _this.addFile(file);
}

function dzInit(_this,mockFile,thumb) {
    _this.options.addedfile.call(_this, mockFile);
    _this.options.thumbnail.call(_this, mockFile, thumb);
    mockFile.previewElement.classList.add('dz-success');
    mockFile.previewElement.classList.add('dz-complete');
}

function dzAccept(file, done, dzElement = '.dropzone', iconBaseUrl) {
    var ext = file.name.split('.').pop().toLowerCase();
    if(['jpg','png','gif','jpeg','bmp'].indexOf(ext) === -1){
        var thumbnail = $(dzElement).find('.dz-preview.dz-file-preview .dz-image:last');
        var icon = iconBaseUrl+"/"+ext+".png";
        thumbnail.css('background-image', 'url('+icon+')');
        thumbnail.css('background-size', 'contain');
    }
    done();
}

/** 
check is there new order or not 
*/
function checkNewOrder()
{  
    $.ajaxSetup({ cache:false });
    $.getJSON(baseUrl+'order.json', function(data) {
    // console.log(data.is_new_order);
    if(data.is_new_order > 0) {
    $('audio').remove();
    var audio = document.createElement("AUDIO");
    document.body.appendChild(audio);
    audio.type="audio/ogg";
    audio.preload="auto";
    audio.controls="controls";
    audio.autoplay="autoplay";
    audio.src = baseUrl+"Audio/Cash_Register.wav";
    audio.play();
    $('#newOrderAlert').modal('show');
    $("#refreshDatatable").trigger("click");
    $.get(baseUrl+"/update-order-notification",function(result) {
        
                console.log(result);
            }).fail(function(error) {
                console.log(error.responseText);
            });
        }
    });
}