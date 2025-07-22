document.addEventListener("DOMContentLoaded", () => {
  // Initialize
  new DragScroll(document.querySelector(".badges-list"));
});

class DragScroll {
  constructor(element) {
    this.element = element;
    this.isDown = false;
    this.startX = 0;
    this.scrollLeft = 0;
    this.velocity = 0;
    this.momentum = 0.95;

    this.addEventListeners();
  }

  addEventListeners() {
    // Mouse events
    this.element.addEventListener("mousedown", this.startDrag.bind(this));
    this.element.addEventListener("mousemove", this.drag.bind(this));
    this.element.addEventListener("mouseup", this.endDrag.bind(this));
    this.element.addEventListener("mouseleave", this.endDrag.bind(this));

    // Touch events
    this.element.addEventListener("touchstart", this.startDrag.bind(this));
    this.element.addEventListener("touchmove", this.drag.bind(this));
    this.element.addEventListener("touchend", this.endDrag.bind(this));
  }

  startDrag(e) {
    this.isDown = true;
    this.element.classList.add("dragging");
    this.startX = this.getX(e) - this.element.offsetLeft;
    this.scrollLeft = this.element.scrollLeft;
  }

  drag(e) {
    if (!this.isDown) return;
    e.preventDefault();
    const x = this.getX(e) - this.element.offsetLeft;
    const walk = (x - this.startX) * 2;
    this.element.scrollLeft = this.scrollLeft - walk;
  }

  endDrag() {
    this.isDown = false;
    this.element.classList.remove("dragging");
  }

  getX(e) {
    return e.type.includes("mouse") ? e.pageX : e.touches[0].pageX;
  }
}
