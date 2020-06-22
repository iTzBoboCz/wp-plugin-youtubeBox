var url = window.location.href;
url = new URL(url);

var get = url.searchParams.get("page");

// nespustí se na stránce se seznamem příspěvků s YT videi
if (get != "slug_yt_box_page") {
  window.onload = function() {
    var addButton = document.getElementById("addInputYT");
    var removeButton = document.getElementById("removeInputYT");
    addButton.onclick = function(event) {
      event.preventDefault();
      addInputYT(addButton, event);
    }
    removeButton.onclick = () => {
      let divs = document.querySelectorAll("[data-remove]");
      for(let i = 0; i < divs.length; i++) {
        if (divs[i].childNodes[0].value == "" || divs[i].childNodes[0].value == null) {
          divs[i].parentNode.removeChild(divs[i]);
          return;
        }
      }
    }
  }
}

// přidání inputu pro další video k příspěvku
function addInputYT(addButton, event) {
  let div = document.createElement("div");
  div.setAttribute("data-remove", "");
  div.innerHTML = "<input type='url' name='yt_links[]' placeholder='https://www.youtube.com/watch?v=123456'>";
  event.path[3].childNodes[1].appendChild(div);
}
