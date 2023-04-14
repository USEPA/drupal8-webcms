import { ButtonView, View, submitHandler } from "ckeditor5/src/ui";
import { icons } from "ckeditor5/src/core";

import MatchListView from "./views/matchlistview";

export default class EpaAddDefinitionView extends View {
  constructor(locale) {
    super(locale);

    this.set("data", []);

    this.listView = new MatchListView(locale);
    this.listView.bind("matches").to(this, "data");

    this.submitButton = this._createButton(
      "Save",
      icons.check,
      "ck-button-save"
    );

    this.submitButton.type = "submit";

    this.cancelButton = this._createButton(
      "Cancel",
      icons.cancel,
      "ck-button-cancel"
    );

    this.cancelButton.delegate("execute").to(this, "cancel");

    this.setTemplate({
      tag: "dialog",
      children: [
        {
          tag: "form",
          children: [this.listView, this.submitButton, this.cancelButton],
        },
      ],
    });
  }

  render() {
    super.render();

    submitHandler({
      view: this,
    });
  }

  show() {
    /** @type {HTMLDialogElement} */ (this.element).showModal();
  }

  hide() {
    /** @type {HTMLDialogElement} */ (this.element).close();
  }

  _createButton(label, icon, className) {
    const button = new ButtonView(this.locale);
    button.set({
      label,
      icon,
      tooltip: true,
      class: className,
    });

    return button;
  }
}
