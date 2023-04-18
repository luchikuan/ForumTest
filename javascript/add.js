var oDragWrap = document.getElementById("attachment");
var wrapper = document.getElementById("page-content-wrapper");
var displayText = document.getElementById("upload-text");
var blurLayer = document.getElementById("blur-layer");

oDragWrap.addEventListener(
    "dragleave",
    function(e) {
        // dragleaveHandler(e);
        e.preventDefault()
        wrapper.setAttribute("style","");
        displayText.setAttribute("style","display:none");
        blurLayer.setAttribute("style","");
    },
    false
);

oDragWrap.addEventListener(
    "drop",
    function(e) {
        // dragleaveHandler(e);
        e.preventDefault()
        wrapper.setAttribute("style","");
        blurLayer.setAttribute("style","");
        displayText.setAttribute("style","display:none");
    },
    false
);

oDragWrap.addEventListener(
    "dragenter",
    function(e) {
        // dragleaveHandler(e);
        e.preventDefault()
        wrapper.setAttribute("style","");
        blurLayer.setAttribute("style","");
        displayText.setAttribute("style","display:none");
    },
    false
);

oDragWrap.addEventListener(
    "dragover",
    function(e) {
        // dragleaveHandler(e);
        e.preventDefault()
        wrapper.setAttribute("style","border:10px dotted #2eb9d4;");
        blurLayer.setAttribute("style","filter: blur(5px);");
        displayText.setAttribute("style","font-size: 90px;font-family: 'Noto Sans TC', sans-serif;position: fixed;");
    },
    false
);




var box = document.getElementById('attachment');
box.addEventListener("drop",
    function(e) {
        //取消默认浏览器拖拽效果
        e.preventDefault();
        var files = [];
        [].forEach.call(e.dataTransfer.files, function(file) {
            files.push(file);
        },false);
        // var reader = new FileReader();
        // reader.onload = function(event){

        // };
        // reader.readAsText();


    },
    false);



//editor
tinymce.init({
    selector: '#mytextarea',
    height: 470,
    menubar: true,
    plugins: [
        'advlist autolink lists link image charmap print preview anchor',
        'searchreplace visualblocks fullscreen',
        'insertdatetime media table contextmenu code',
        'wordcount ',
        "paste"
    ],
    toolbar: 'undo redo | insert | styleselect | bold italic formatpainter permanentpen pageembed | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image |  code ',
    toolbar_drawer: 'sliding',
    permanentpen_properties: {
        fontname: 'arial,helvetica,sans-serif',
        forecolor: '#FF0000',
        fontsize: '18pt',
        hilitecolor: '',
        bold: true,
        italic: false,
        strikethrough: false,
        underline: false
    },
    table_toolbar: "tableprops cellprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol",
    powerpaste_allow_local_images: true,
    powerpaste_word_import: 'prompt',
    powerpaste_html_import: 'prompt',
    spellchecker_language: 'en',
    spellchecker_dialog: true,
    branding: false,
    paste_data_images: true
});


