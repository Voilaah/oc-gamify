document.addEventListener("DOMContentLoaded", () => {
  new DragScroll(
    document.querySelector(".badges-list"),
    window.matchMedia("(min-width: 1024px)")
  );
});

class DragScroll {
  constructor(element, queryMedia) {
    this.element = element;
    this.isDown = false;
    this.startX = 0;
    this.scrollLeft = 0;
    this.velocity = 0;
    this.momentum = 0.95;
    this.queryMedia = queryMedia;
    this.eventListeners = false;

    this.startDragFn = this.startDrag.bind(this);
    this.dragFn = this.drag.bind(this);
    this.endDragFn = this.endDrag.bind(this);

    window.addEventListener('resize', () => {
      if (this.queryMedia.matches) {
        if (this.eventListeners == false) {
          this.addEventListeners();
          this.eventListeners = true;
        }
      }
      else {
        if (this.eventListeners == true) {
          this.removeEventListeners()
          this.eventListeners = false;
        }
      }
    })
  }

  removeEventListeners() {
    // Mouse events
    this.element.removeEventListener("mousedown", this.startDragFn);
    this.element.removeEventListener("mousemove", this.dragFn);
    this.element.removeEventListener("mouseup", this.endDragFn);
    this.element.removeEventListener("mouseleave", this.endDragFn);

    // Touch events
    this.element.removeEventListener("touchstart", this.startDragFn);
    this.element.removeEventListener("touchmove", this.dragFn);
    this.element.removeEventListener("touchend", this.endDragFn);
  }

  addEventListeners() {
    // Mouse events
    this.element.addEventListener("mousedown", this.startDragFn);
    this.element.addEventListener("mousemove", this.dragFn);
    this.element.addEventListener("mouseup", this.endDragFn);
    this.element.addEventListener("mouseleave", this.endDragFn);

    // Touch events
    this.element.addEventListener("touchstart", this.startDragFn);
    this.element.addEventListener("touchmove", this.dragFn);
    this.element.addEventListener("touchend", this.endDragFn);
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
