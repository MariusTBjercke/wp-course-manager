export default class CourseSlider {
  constructor() {
    this.init();
  }

  private init(): void {
    document.addEventListener('DOMContentLoaded', () => {
      this.initSliders();
    });
  }

  private initSliders(): void {
    const sliders = document.querySelectorAll('.cm-course-slider');

    sliders.forEach(slider => {
      const wrapper = slider.querySelector('.cm-slider-wrapper') as HTMLElement;
      const container = slider.querySelector('.cm-slider-container') as HTMLElement;
      const items = slider.querySelectorAll('.cm-slider-item');
      const prevBtn = slider.querySelector('.cm-slider-prev') as HTMLButtonElement;
      const nextBtn = slider.querySelector('.cm-slider-next') as HTMLButtonElement;
      let currentIndex = 0;

      const updateSlider = () => {
        const offset = -currentIndex * 100; // Flytt 100% per slide
        container.style.transform = `translateX(${offset}%)`;
        console.log('Current index:', currentIndex, 'Offset:', offset);
      };

      prevBtn.addEventListener('click', () => {
        if (currentIndex > 0) {
          currentIndex--;
          updateSlider();
        }
      });

      nextBtn.addEventListener('click', () => {
        if (currentIndex < items.length - 1) {
          currentIndex++;
          updateSlider();
        }
      });

      setInterval(() => {
        if (currentIndex < items.length - 1) {
          currentIndex++;
        } else {
          currentIndex = 0;
        }
        updateSlider();
      }, 5000);

      // Initialize slider on load
      updateSlider();
    });
  }
}