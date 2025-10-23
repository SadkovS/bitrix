BX.ready(function () {
    let mainFrame = document.getElementById("main-iframe");
    let mainAria = document.getElementById("main-aria");
    let loader = document.getElementById("loader");
    mainFrame.onload = function(e) {
        let request = "";
        mainFrame.contentWindow.postMessage({
            'event': 'message',
            'openFrame': "true"
        }, "*");
        window.addEventListener("message", function (event) {
            if (event.data.openFrame === "Y") {
                request = "true";
                mainFrame.style.display = "block";
                loader.style.display = "none";
            }
        }, false);
        setTimeout(() => {
            if ( request !== "true" ) {
                mainAria.classList.add("no-connect");
                loader.style.display = "none";
            }
        }, 10000);
    }

    mainFrame.onerror = function() {
        mainAria.classList.add("no-connect");
        loader.style.display = "none";
    }

    window.addEventListener("message", function (event) {
        if(event.data.sizeChangeMain === "Y"){
            mainFrame.style.height = String(event.data.height) + "px";
        }
    }, false);

});
