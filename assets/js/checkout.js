document.addEventListener("DOMContentLoaded", () => {
  const input = document.querySelector("#billing_delivery_display");

  const delivery = new Date();

  const deliveryToday = window.canDeliverToday;
  const { exceptionDates } = window;
  const { noDeliveryDates } = window;

  const dayOfWeek = delivery.getDay();
  const afternoon = delivery.getHours() > 13;

  /**
   * Format date
   * @param {date} date Date object to be formatted
   * @returns Date string formatted
   */
  function formatDate(date) {
    return date.toISOString().split("T")[0];
  }

  /**
   * Modify the date to next day
   * @param {date} date Date object
   */
  function nextDay(date) {
    date.setDate(date.getDate() + 1);
  }

  function isHoliday(date) {
    const dateString = formatDate(date);
    if (!exceptionDates.includes(dateString)) {
      if (date.getDay() === 0 || noDeliveryDates.includes(dateString)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Check if date is a Sunday or holiday and if so, skip to the next day
   * @param {date} date Date object
   */
  function notHoliday(date) {
    if (isHoliday(date)) {
      nextDay(date);
      notHoliday(date);
    }
  }

  /**
   * Check what the earliest delivery date could be
   * @param {date} date Date object
   * @returns New date when delivery can happen
   */
  function earliestDeliveryDate(date) {
    if (dayOfWeek === 6 || dayOfWeek === 0) {
      date.setHours(0, 0, 0, 0);
      nextDay(date);
    } else if (dayOfWeek === 5 && afternoon && !deliveryToday) {
      date.setHours(0, 0, 0, 0);
      nextDay(date);
      nextDay(date);
    } else if (afternoon || !deliveryToday) {
      nextDay(date);
      date.setHours(0, 0, 0, 0);
    }
    notHoliday(date);
    return date;
  }

  function getValidationP() {
    let validation = document.querySelector(".alzr-validation");

    if (!validation) {
      validation = document.createElement("p");
      validation.classList.add("alzr-validation");
      document.querySelector("#billing_delivery_display_field").appendChild(validation);
    }

    return validation;
  }

  const nextAvailableDelivery = earliestDeliveryDate(delivery);

  /**
   * Handler for input field
   * @param {event} e Input event
   */
  function validateDeliveryDate(e) {
    const field = e.target;
    const date = new Date(`${field.value}T00:00`);
    const validation = getValidationP();
    const billingDelivery = document.querySelector("#billing_delivery");

    if (isHoliday(date)) {
      validation.innerHTML = "Lo sentimos, no hacemos entregas domingos ni festivos. Por favor escoge otra fecha.";
      field.classList.add("alzr-invalid");
      billingDelivery.value = "holiday";
    } else if (date < nextAvailableDelivery) {
      validation.innerHTML = "Lo sentimos, no podemos entregar este d&iacute;a. Por favor escoge una fecha posterior.";
      field.classList.add("alzr-invalid");
      billingDelivery.value = "invalid";
    } else {
      validation.innerHTML = "";
      field.classList.remove("alzr-invalid");
      billingDelivery.value = field.value;
    }
  }

  const nextAvailableDeliveryString = formatDate(nextAvailableDelivery);

  input.setAttribute("min", nextAvailableDeliveryString);
  input.setAttribute("pattern", "d{4}-d{2}-d{2}");
  input.addEventListener("input", validateDeliveryDate);
  input.addEventListener("click", () => {
    try {
      input.showPicker();
    } catch {}
  });

  input.value = nextAvailableDeliveryString;
  document.querySelector("#billing_delivery").value = nextAvailableDeliveryString;
});
