"use strict";

const defaultWindowOptions = {};
const defaultDialogOptions = {
  isTitle: true,
  isCancelBtn: true,
  isOkBtn: true,
  cancelBtnText: "Cancel",
  okBtnText: "Continue",
};

function sirvUIRenderModalWindow(children, type, windowOptions={}) {
    let dialogContainer = document.querySelector('.sirv-ui-modal-dialogs-container');

    const template = `
      <dialog class="sirv-ui-modal-dialog sirv-ui-modal-dialog-${type}">
        <button type="button" class="sirv-modal-window-close-button sirv-modal-window-close-action">✕</button>
        <div class="sirv-ui-modal-dialog-container">
          ${children}
        </div>
      </dialog>
    `;

    if (dialogContainer) {
      dialogContainer.innerHTML += template;
    }else{
      dialogContainer = document.createElement("div");
      dialogContainer.classList.add("sirv-ui-modal-dialogs-container");
      dialogContainer.innerHTML = template;

      document.body.appendChild(dialogContainer);
    }
}

function sirvUIGetControlsTemplate(options) {
  const defaultCancelBtnTemplate = `
    <button autofocus class="sirv-ui-btn sirv-ui-button-secondary sirv-ui-dialog-cancel-btn sirv-modal-window-close-action">${options.cancelBtnText || 'Cancel'}</button>
  `;
  const defaultokBtnTemplate = `<button class="sirv-ui-btn sirv-ui-button-primary sirv-ui-dialog-ok-btn">${options.okBtnText || 'Continue'}</button>`;


  const controlsTemplate = `
    <div class="sirv-ui-dialog-block-controls">
      ${options.isCancelBtn == false ? "" : defaultCancelBtnTemplate}
      ${options.isOkBtn == false ? "" : defaultokBtnTemplate}
    </div>
  `;

  return controlsTemplate;
}


function sirvUIGetTitleTemplate(options) {
  const defaultTitleTemplate = `
    <div class="sirv-ui-dialog-block-title">
      <h1></h1>
    </div>`;

    return options.isTitle == false ? "" : defaultTitleTemplate;
}


function sirvUIAddDialogHTMLToPage(dialogType, dialogContentTemplate, dialogOptions = {}) {
  dialogOptions = {...defaultDialogOptions, ...dialogOptions};

  const moduleTitle = sirvUIGetTitleTemplate(dialogOptions);
  const moduleControls = sirvUIGetControlsTemplate(dialogOptions);

  const baseTemplate = `
    <div class="sirv-ui-dialog-data-content">
      ${moduleTitle}
      <div class="sirv-ui-dialog-block-content">
        ${dialogContentTemplate}
      </div>
      ${moduleControls}
    </div>
  `;

  sirvUIRenderModalWindow(baseTemplate, dialogType);
}


function sirvUIAddConfirmDialogHTMLToThePage(){
  sirvUIAddDialogHTMLToPage('confirm', '');
}


function sirvUIAddInformDialogHTMLToThePage(){
  sirvUIAddDialogHTMLToPage("inform", "", { isOkBtn: false, cancelBtnText : "Close"});
}


function sirvUIAddInputDialogHTMLToThePage(){
  const inputTemplate = `
    <label for="sirv-ui-input-text" class="sirv-ui-input-dialog-label"></label>
    <input type="text" name="sirv-ui-input-text" class="sirv-ui-input-text" placeholder="">
  `;

  sirvUIAddDialogHTMLToPage("input", inputTemplate, { okBtnText : "Create" });
}


function sirvUIAttachCallback(callback, btnEl, dialogEl) {
  btnEl.addEventListener("click", callback);

  //prevent from duplicate events
  dialogEl.addEventListener("close", function handler() {
    if (!!btnEl) {
      btnEl.removeEventListener("click", callback);
    }
    dialogEl.removeEventListener("close", handler);
  });

  document.addEventListener("sirv-ui-modal-dialog-close-event", function handler() {
      if (!!btnEl) {
        btnEl.removeEventListener("click", callback);
      }
      document.removeEventListener("sirv-ui-modal-dialog-close-event", handler);
    }
  );
}


function sirvUIShowInformDialog(title, desc, dialogOptions = {}){
  const informDialog = document.querySelector(".sirv-ui-modal-dialog-inform");

  const titleEl = informDialog.querySelector(".sirv-ui-dialog-block-title h1");
  const descEl = informDialog.querySelector(".sirv-ui-dialog-block-content");

  if (titleEl) titleEl.innerText = title;
  if (descEl) descEl.innerHTML = desc;

  informDialog.showModal();
}


function sirvUIShowConfirmDialog(title, desc, yesCallback=null, yesCallbackOptions = [], dialogOptions = {}){
  const confirmDialog = document.querySelector(".sirv-ui-modal-dialog-confirm");

  if(yesCallback){
    const btnEl = confirmDialog.querySelector(".sirv-ui-dialog-ok-btn");

    if(btnEl){
      const okBtnHandler = function () {
        confirmDialog.close();

        yesCallback(...yesCallbackOptions);
      };
      sirvUIAttachCallback(okBtnHandler, btnEl, confirmDialog);
    }
  }

  const titleEl = confirmDialog.querySelector(".sirv-ui-dialog-block-title h1");
  const descEl = confirmDialog.querySelector(".sirv-ui-dialog-block-content");

  if (titleEl) titleEl.innerText = title;
  if (descEl) descEl.innerText = desc;

  confirmDialog.showModal();
}


function sirvUIShowInputDialog (title, inputLabelText, inputPlaceholderText='', yesCallback=null, yesCallbackOptions = [], dialogOptions = {}){
  const inputDialog = document.querySelector(".sirv-ui-modal-dialog-input");

  const titleEl = inputDialog.querySelector(".sirv-ui-dialog-block-title h1");
  const input = inputDialog.querySelector(".sirv-ui-dialog-block-content input.sirv-ui-input-text");
  const inputLabel = inputDialog.querySelector(".sirv-ui-dialog-block-content .sirv-ui-input-dialog-label");

  if(yesCallback){
    const btnEl = inputDialog.querySelector(".sirv-ui-dialog-ok-btn");

    if (btnEl){
      const okBtnHandler = function () {
        inputDialog.close();

        yesCallbackOptions.push(input.value);
        yesCallback(...yesCallbackOptions);
      };
      sirvUIAttachCallback(okBtnHandler, btnEl, inputDialog);
    }
  }

  titleEl.innerText = title;
  inputLabel.innerText = inputLabelText;
  input.placeholder = inputPlaceholderText;

  inputDialog.showModal();
}


function sirvUIBindCloseAction() {
  const closeButtons = document.querySelectorAll('.sirv-modal-window-close-action');

  closeButtons.forEach((button) => {
    button.addEventListener('click', (event) => {
      const modalDialog = button.closest('.sirv-ui-modal-dialog');
      modalDialog.close();

      const closeDialogevent = new Event("sirv-ui-modal-dialog-close-event");
      document.dispatchEvent(closeDialogevent);
    });
  });
}


function sirvUIInitialize() {
  sirvUIAddConfirmDialogHTMLToThePage();
  sirvUIAddInformDialogHTMLToThePage();
  //sirvUIAddInputDialogHTMLToThePage();

  sirvUIBindCloseAction();
}


document.addEventListener("DOMContentLoaded", function () {
  sirvUIInitialize();
});
