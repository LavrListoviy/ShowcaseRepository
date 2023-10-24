document.addEventListener("DOMContentLoaded", async () => {
  //Сдвиг плейсхолдеров в форме
  let inputsForm = document.querySelectorAll(".js-input");
  let placeholders = document.querySelectorAll(".placeholder");
  if (inputsForm.length > 0) {
    inputsForm.forEach((el) => {
      let placeholder = el.parentNode.querySelector(".form__placeholder");
      el.addEventListener("focus", () => {
        placeholder.classList.add("active");
      });
      el.addEventListener("blur", () => {
        if (!el.value.length > 0) {
          placeholder.classList.remove("active");
        }
      });
    });

    placeholders.forEach((el) => {
      el.addEventListener("click", () => {
        el.parentNode.querySelector(".js-input").focus();
      });
    });
  }

  //Валидация формы
  let form = document.querySelector(".form");
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      let validateInputs = form.querySelectorAll("input[data-rule]");
      let ch = 0;

      validateInputs.forEach((input) => {
        switch (input.getAttribute("data-rule")) {
          case "name":
            if (input.value.length > 1) {
              if (/^[a-zA-Z]+$/.test(input.value)) {
                ch++;
              } else {
                input.classList.add("error");
                input.parentNode.querySelector(".form__error").textContent =
                  "The name must contain only latin characters";
                input.parentNode
                  .querySelector(".form__error")
                  .classList.add("active");
              }
            } else {
              input.classList.add("error");
              input.parentNode.querySelector(".form__error").textContent =
                "Minimum number of characters 2";
              input.parentNode
                .querySelector(".form__error")
                .classList.add("active");
            }
            break;
          case "login":
            document.querySelector(
              "body > section > form > div > label:nth-child(1) > span"
            ).style.color = "";
            if (input.value.length > 1) {
              if (/^[a-zA-Z0-9_-]+$/.test(input.value)) {
                ch++;
              } else {
                input.classList.add("error");
                input.parentNode.querySelector(".form__error").textContent =
                  'Login must contain only latin characters, numbers and symbols "-", "_"';
                input.parentNode
                  .querySelector(".form__error")
                  .classList.add("active");
              }
            } else {
              input.classList.add("error");
              input.parentNode.querySelector(".form__error").textContent =
                "Minimum number of characters 2";
              input.parentNode
                .querySelector(".form__error")
                .classList.add("active");
            }
            break;
          case "telegram":
            if (input.value.length > 1) {
              if (/^[0-9]+$/.test(input.value)) {
                ch++;
              } else {
                input.classList.add("error");
                input.parentNode.querySelector(".form__error").textContent =
                  "Telegram ID can only contain numbers";
                input.parentNode
                  .querySelector(".form__error")
                  .classList.add("active");
              }
            } else {
              input.classList.add("error");
              input.parentNode.querySelector(".form__error").textContent =
                "Minimum number of characters 2";
              input.parentNode
                .querySelector(".form__error")
                .classList.add("active");
            }
            break;
          case "last_name":
            if (input.value.length > 1) {
              if (/^[a-zA-Z]+$/.test(input.value)) {
                ch++;
              } else {
                input.classList.add("error");
                input.parentNode.querySelector(".form__error").textContent =
                  "The name must contain only latin characters";
                input.parentNode
                  .querySelector(".form__error")
                  .classList.add("active");
              }
            } else {
              input.classList.add("error");
              input.parentNode.querySelector(".form__error").textContent =
                "Minimum number of characters 2";
              input.parentNode
                .querySelector(".form__error")
                .classList.add("active");
            }
            break;
          case "password":
            if (input.value.length > 5) {
              ch++;
            } else {
              input.classList.add("error");
              input.parentNode.querySelector(".form__error").textContent =
                "Minimum number of characters 6";
              input.parentNode
                .querySelector(".form__error")
                .classList.add("active");
            }
            break;
        }

        input.addEventListener("input", () => {
          input.classList.remove("error");
          input.parentNode.querySelector(".form__error").textContent = "";
          input.parentNode
            .querySelector(".form__error")
            .classList.remove("active");
        });
      });

      if (ch === validateInputs.length) {
        let inputs = form.querySelectorAll("input");
        let formData = new FormData();
        for (i of inputs) {
          if (i.name != "rang" || i.checked) {
            formData.append(i.name, i.value);
          }
        }
        switch (document.querySelector("head > title").textContent) {
          case "OMConnector - Login":
            fetch("../user_control/login.php", {
              method: "POST",
              body: formData,
            })
              .then((r) => r.json())
              .then((d) => {
                document.getElementById("login_error_container").textContent =
                  "";
                document
                  .getElementById("login_error_container")
                  .classList.remove("active");
                document.getElementById(
                  "password_error_container"
                ).textContent = "";
                document
                  .getElementById("password_error_container")
                  .classList.remove("active");
                if (d.status == 2) {
                  window.location.replace("../create-user");
                } else if (d.status == "Wrong password") {
                  document.getElementById(
                    "password_error_container"
                  ).textContent = "Wrong password";
                  document
                    .getElementById("password_error_container")
                    .classList.add("active");
                } else if (d.status == "No such login in system") {
                  document.getElementById("login_error_container").textContent =
                    "Wrong login";
                  document
                    .getElementById("login_error_container")
                    .classList.add("active");
                } else if (d.status == "Access denied") {
                  document.getElementById("login_error_container").textContent =
                    "Access denied";
                  document
                    .getElementById("login_error_container")
                    .classList.add("active");
                }
              });
            break;
          case "OMConnector - Create account":
            fetch("../user_control/create_user.php", {
              method: "POST",
              body: formData,
            })
              .then((r) => r.json())
              .then((d) => {
                if (!d.error) {
                  for (i of inputs) {
                    i.value = "";
                    if (i.parentNode.querySelector(".form__placeholder")) {
                      i.parentNode
                        .querySelector(".form__placeholder")
                        .classList.remove("active");
                    }
                  }
                  document.querySelector(
                    "body > section > form > div > label:nth-child(1) > span"
                  ).textContent = "User registered successfully";
                  document
                    .querySelector(
                      "body > section > form > div > label:nth-child(1) > span"
                    )
                    .classList.add("active");
                  document.querySelector(
                    "body > section > form > div > label:nth-child(1) > span"
                  ).style.color = "limegreen";
                } else {
                  alert(d.error);
                }
              });
            break;
        }
      }
    });
  }

  //Селекты
  let selects = document.querySelectorAll(".select");
  if (selects.length > 0) {
    selects.forEach((el) => {
      el.addEventListener("click", () => {
        el.classList.toggle("active");
      });

      let title = el.querySelector(".select__title");
      let selectInputs = el.querySelectorAll(".select__label");
      selectInputs.forEach((input) => {
        input.addEventListener("click", () => {
          title.textContent = input.textContent;
          title.value = input.parentElement.querySelector('.select__input').value;
          el.classList.remove("active");
        });
      });
    });

    document.addEventListener("click", (e) => {
      selects.forEach((el) => {
        const targetElement = e.target;
        if (!el.contains(targetElement)) {
          el.classList.remove("active");
        }
      });
    });
  }


  //Копировать пароль, генерация пароля и показать/скрыть пароль

  let inputPassword = document.querySelector(".js-password");
  if (inputPassword) {
    //Копирование пароля
    const btnCopyPassword = document.querySelector(".js-copy__button");
    const passwordDisplay = document.getElementById("password-display");
    btnCopyPassword.addEventListener("click", () => {
      inputPassword.style.display = "none";
      passwordDisplay.style.display = "block";
      passwordDisplay.innerText = inputPassword.value;
      console.log(passwordDisplay.innerText);
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard
          .writeText(passwordDisplay.innerText)
          .then(() => {
            // Текст успешно скопирован
            inputPassword.style.display = "block";
            passwordDisplay.style.display = "none";
            console.log("copied by new method");
          })
          .catch((err) => {
            console.error("Error copying text: ", err);
          });
      } else {
        inputPassword.style.display = "block";
        passwordDisplay.style.display = "none";
        const tempInput = document.createElement("textarea");
        tempInput.value = passwordDisplay.innerText;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand("copy");
        document.body.removeChild(tempInput);
        inputPassword.style.display = "block";
        passwordDisplay.style.display = "none";
        console.log("copied by old method");
      }
    });

    //Показать/скрыть пароль
    let btnShowPassword = document.querySelector(".js-toggle__password");
    let btnShowPassIcon = document.querySelector(".js-show__password");
    let btnHiddenPassIcon = document.querySelector(".js-hidden__password");
    btnShowPassword.addEventListener("click", () => {
      if (inputPassword.type === "password") {
        inputPassword.type = "text";
        btnShowPassIcon.classList.remove("active");
        btnHiddenPassIcon.classList.add("active");
      } else {
        inputPassword.type = "password";
        btnShowPassIcon.classList.add("active");
        btnHiddenPassIcon.classList.remove("active");
      }
    });

    //Сгенерировать пароль
    let btnGeneratePassword = document.querySelector(".js-generate__button");
    btnGeneratePassword.addEventListener("click", () => {
      inputPassword.value = generatePassword();
      inputPassword.parentNode
        .querySelector(".form__placeholder")
        .classList.add("active");
      inputPassword.classList.remove("error");
      inputPassword.parentNode.querySelector(".form__error").textContent = "";
      inputPassword.parentNode
        .querySelector(".form__error")
        .classList.remove("active");
    });
  }

  function generateNumbers() {
    return String.fromCharCode(Math.floor(Math.random() * 10) + 48);
  }

  function generateLowerCase() {
    return String.fromCharCode(Math.floor(Math.random() * 26) + 97);
  }

  function generateUpperCase() {
    return String.fromCharCode(Math.floor(Math.random() * 26) + 65);
  }

  function generateRandSymbol() {
    const symbols = `!@#$%^&*()_-+=<>/?|`;
    return symbols[Math.floor(Math.random() * symbols.length)];
  }

  function generatePassword() {
    let lng = Math.floor(Math.random() * 5) + 10;
    let genPass = "";
    for (let i = 0; i < lng; i += 1) {
      let randomFunc = [
        generateLowerCase(),
        generateUpperCase(),
        generateNumbers(),
        generateRandSymbol(),
      ];
      genPass += randomFunc[Math.floor(Math.random() * randomFunc.length)];
    }
    return genPass;
  }

  function copyToClipboard() {
    let copyText = document.getElementsByName("password")[0];
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
  }

  function genThenCopy() {
    let p = generatePassword();
    document.getElementById("P1").value = p;
    copyToClipboard();
  }

  //Чекбоксы

  let mainCheckbox = document.querySelector(".js-checkbox-main");

  if (mainCheckbox) {
    let checkboxes = document.querySelectorAll(".js-checkbox");

    mainCheckbox.addEventListener("click", () => {
      checkboxes.forEach((el) => {
        el.checked = mainCheckbox.checked;
      });
    });
  }
  //Обсервер для выпадающего списка сессий

  let dds = document.querySelector("#dropdown_sessions");
  const observerSettings = {
    childList: true,
    subtree: true,
  };
  const obs = new MutationObserver((mutationList) => {
    mutationList.forEach(mutation => {
      let title = document.querySelector("#selector_session");
      if (mutation.type === 'childList') {
        mutation.addedNodes.forEach(added => {
          added.querySelector(".select__label").addEventListener("click", () => {
            title.textContent = added.querySelector(".select__label").textContent;
            title.value = added.querySelector(".select__input").value;
            document.querySelector("#session_select").classList.remove('active');

          });
        })
      }
    })
  })
  if (dds !== null) {
    obs.observe(dds, observerSettings);
  }
  //Вставка value в h3 и другие костыли
  if (document.querySelector("#log_type") != null) {
    document.querySelector("#log_type").value = "summary";
  }
  if (document.querySelector("#selector_name") != null) {
    document.querySelector("#selector_name").value = "placeholder";
  }
  if (document.querySelector("#selector_session") != null) {
    document.querySelector("#selector_session").value = "placeholder";
  }
  //-------------------------------------------------------------------
  if (document.querySelector("#summary") != null) {
    document.querySelector("#summary").checked = true;
  }
  //___________________________________________________________________
});