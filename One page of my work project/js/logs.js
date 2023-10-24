document.addEventListener("DOMContentLoaded", () => {
    const slider = document.getElementById('max_rem_interval');
    const sliderValue = document.getElementById('slider_value');
    // Обработчик события изменения положения ползунка
    slider.addEventListener('input', function () {
        // Обновляем значение в элементе, чтобы отображать текущее значение ползунка
        sliderValue.textContent = slider.value;
    });
    slider.addEventListener("mousedown", function () {
        this.classList.add("pressed");
    });
    slider.addEventListener("mouseup", function () {
        this.classList.remove("pressed");
    });

    document.querySelector("#name__selector_dropdown").addEventListener("change", fillSessions);
    document.querySelector("#filter__button").addEventListener("click", async ()=>{
        let logsType = document.querySelector("#log_type").value;
        let userID = document.querySelector("#selector_name").value;
        let session = document.querySelector("#selector_session").value;
        let delay = document.querySelector("#max_rem_interval").value;
        fillLogs(logsType, userID, session, delay);
    });
})
async function fillSessions() {
    const nameValue = document.querySelector("#selector_name").value;
    let body = { "userID": nameValue };
    let response = await fetch('/logs-panel/get-sessions.php', {
        method: "POST",
        body: JSON.stringify(body)
    }).then(r => r.text())
    document.querySelector("#selector_session").textContent = "Session date";
    return document.querySelector('#dropdown_sessions').innerHTML = response;
}
async function fillLogs(logsType, userID, session, followUpDelay){
    let body = new FormData();
    body.append("logs_type", logsType);
    body.append("user_id", userID);
    body.append("session_id", session);
    body.append("delay", followUpDelay);
    let response = await fetch("/logs-panel/get-logs.php", {
        method: "POST",
        body: body
    }).then(r=>r.text());
    return document.querySelector("body > section > div").innerHTML = response;
}
