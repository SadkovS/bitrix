function showToast(data) {
    let msg, style, node, wrapper;
    wrapper = document.createElement('div');
    wrapper.classList.add('toast__wrapper')
    node = document.createElement('div');
    node.classList.add('title');
    wrapper.appendChild(node);
    msg = document.createElement('ul');
    msg.classList.add('toast__msg');
    wrapper.appendChild(msg);

    if (data.status === 'success') {
        msg.textContent = data.message || "";
        node.textContent = data.title || 'Сохранение';
        node.style.color = '#021231';
        style = {
            background: "#fff",
            padding: "1.5rem 2.5rem 1rem 2.5rem",
            color: "#021231",
            borderRadius: "15px",
            width: "auto",
            maxWidth: "none",
            // border: "1px solid #75C39C",
        }
    } else if (data.status === 'error') {
        let msgs = data.message.split(';');
        node.style.color = '#C92341';
        msgs.forEach((item) => {
            const li = document.createElement('li');
            li.textContent = item;
            msg.appendChild(li);
        })
        node.textContent = 'Ошибка'
        style = {
            background: "#fff",
            padding: "1.5rem 2.5rem",
            color: "#C92341",
            borderRadius: "15px",
            width: "400px",
        }
    }
    Toastify({
        // text: msg,
        node: wrapper,
        duration: 3000,
        newWindow: true,
        gravity: "bottom",
        position: "left",
        stopOnFocus: true,
        style: style,
        close: true,
        onClick: function () {
        } // Callback after click
    }).showToast();
}

function supportOnSearchParam() {
    const search = window.location.search;
    const str = "support_form=Y";
    if (!search.includes(str)) { return };
    setTimeout(() => {
        window.__popup && window.__popup.open("#support-popup");
    }, 0)
}

function getHtml(url_page) {
    addLoader();

    $.ajax({
        url: url_page,
        method: 'get',
        dataType: 'html',
        success: function (result) {
            $("main").replaceWith($(result).find("main"));
            history.pushState(null, null, url_page);
            window.__popup.close('#filter');

            removeLoader();
        }
    });
}

function addLoader() {
    document.body.classList.add('loading');
}
function removeLoader() {
    document.body.classList.remove('loading');
}

$(document).ready(function () {
    $(document).on("click", "[data-link-copy]", function () {
        $(this).closest(".card__item").removeClass("show-tooltip");

        showToast({ status: "success", title: "Ссылка скопирована" });
    })

    /*$(document).on("submit", "form.filter__selected-tags",  function (e) {
        e.preventDefault();

        let url = $(this).attr("action");

        getHtml(url);
    });*/


    supportOnSearchParam();
})

window.addEventListener('load', function () {
    setTimeout(() => document.body.classList.remove('loading'), 200)
})
setTimeout(() => document.body.classList.remove('loading'), 2000);



// // Глобальная переменная для отслеживания состояния курсора
let isMouseOverSeatMap = false;
let mousemoveHandler = null;
window.__mouseOverSeatMap = false;

// Именованная функция для обработки mousemove
function handleSeatMapMouseMove(e) {
    if (!isMouseOverSeatMap) return;
    e.preventDefault();
    e.stopPropagation();
}

function mouseOverSeatMap({ target }) {
    if (target.closest('#seatmap-schema svg') || target.closest('#seatmap-schema canvas')) {
        isMouseOverSeatMap = true;
        window.__mouseOverSeatMap = true;
        // Добавляем обработчик только если его еще нет
        if (!mousemoveHandler) {
            mousemoveHandler = handleSeatMapMouseMove;
            document.addEventListener('mousemove', mousemoveHandler);
        }
    }
}

function mouseOutSeatMap({ target }) {
    if (target.closest('#seatmap-schema svg') || target.closest('#seatmap-schema canvas')) {
        isMouseOverSeatMap = false;
        window.__mouseOverSeatMap = false;
        // Удаляем обработчик mousemove
        if (mousemoveHandler) {
            document.removeEventListener('mousemove', mousemoveHandler);
            mousemoveHandler = null;
        }
    }
}

document.addEventListener('mouseover', mouseOverSeatMap);
document.addEventListener('mouseout', mouseOutSeatMap);

