$(window).on('load', function() {
    // Wait until the window is fully loaded


    const productId = getProductIdFromURL(); // Get product ID from the URL
    //alert(productId); // Now it's safe to use `productId` because the window is fully loaded
    fetchProductDetails(productId); // Fetch and display product details based on the product ID
});

// Helper function to extract product ID from the URL (you can modify this to suit your URL structure)
function getProductIdFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('productId'); // Assuming the product ID is passed as a query param
}

function fetchProductDetails(productId) {
    // alert("Page Loaded 1");
    $.get('./js/products.json', function(products) {
            const product = products.find(p => p.id === productId); // Find the product by ID
            if (product) {
                populateProductDetails(product); // Call function to populate the details
                placeOrder(product);
                localStorage.setItem('savedProduct', JSON.stringify(product));

            } else {
                console.error("Product not found!");
            }
        })
        .fail(function(error) {
            console.error("Error fetching the product data:", error);
        });
}

// Function to populate product details in the HTML
function populateProductDetails(product) {
    function buyNow(productId) {
        // Redirect to order_summary.html with productId in the URL
        window.location.href = `order_summary.html?productId=${product.id}`;
    }
    // alert("work")
    console.log(product)
    // Assuming `carouselInner` is the container for your carousel items
    const carouselInner = $(".carousel-inner");
    carouselInner.empty(); // Clear any previous carousel items

    // Iterate over image properties dynamically (img1, img2, img3, etc.)
    let i = 1;
    while (product[`img${i}`]) {
        const image = product[`img${i}`]; // Get the image by dynamically accessing the property
        const isActive = i === 1 ? 'active' : ''; // Set the first image as active
        const carouselItem = $('<div>', {
            class: `carousel-item ${isActive}`
        }); // Create a new carousel item div
        const imgElement = $('<img>', {
            class: 'd-block img-fluid m-auto', // Bootstrap classes
            style: 'height: 400px;',
            src: image, // Image source from the product
            alt: product.name // Alt text for accessibility
        });

        // Append the image to the carousel item and then the item to the carousel
        carouselItem.append(imgElement);
        carouselInner.append(carouselItem);

        i++; // Increment to check the next imgX property (img1, img2, etc.)
    }


    // Set the product title

    // Set the product price, MRP, and discount
    $('.discount-amt').text(`-₹${product.mrp - product.selling_price}`);
    // $('.detls-dis').text(`-₹${product.mrp - product.selling_price} off`);
    $('.mrp').text(product.mrp);
    $('.mrp').text(product.mrp);
    $('.price').text(`₹${product.selling_price}`);
    $('.paymet-price').text(`₹${product.selling_price}`);
    $('.product-title').text(product.name);


    // Set the product price, MRP, and discount
    $('.discount').text(`${product.discount || '90%'} off`); // Set discount, default to '0%' if not available

    $('.mrp').text(`₹${product.mrp}`); // Set MRP with ₹ symbol
    $('.selling_price').text(`₹${product.selling_price}`); // Set selling price with ₹ symbol

    // Set the product image
    $('#item_image').attr('src', product.img1); // Set product image

    // Optionally, you can also update additional fields if needed, like the quantity
    $('.qty').text(`Qty: 1`); // Set quantity (assuming 1 as default)

    // Set the product features
    const featureContainer = $('.product-extra');
    featureContainer.empty(); // Clear existing items

    // Assuming the `features` property is stored as an HTML string
    const featuresHtml = product.fetaures; // Get the features HTML

    // Append the HTML content directly to the feature container
    featureContainer.html(featuresHtml); // This will insert all the HTML at once

    // Set product details images
    const productDetailsContainer = $('.product-details');
    productDetailsContainer.empty(); // Clear existing images

    // Assuming the product has img1, img2, img3, ..., img5 as individual image properties
    const imageKeys = ['img1', 'img2', 'img3', 'img4', 'img5']; // List all the image keys

    imageKeys.forEach(imageKey => {
        const imageUrl = product[imageKey]; // Get the image URL from the product object

        if (imageUrl) { // Check if the image URL exists
            const imgElement = $('<img>').attr('src', imageUrl).css('max-width', '100%'); // Create an <img> element
            productDetailsContainer.append(imgElement); // Append the image to the container
        }
    });




}


function buyNow() {
    const productId = getProductIdFromURL();
    // Redirect to order_summary.html with productId in the URL
    window.location.href = `address.html?productId=${productId}`;
}

function btnContinue() {
    const productId = getProductIdFromURL();
    // Redirect to order_summary.html with productId in the URL
    window.location.href = `payment.html?productId=${productId}`;
}

function placeOrder(product) {
    const paymentMethods = document.querySelectorAll('.available-method');
    let selectedPaymentUrl = null;
    console.log(product);

    paymentMethods.forEach(method => {
        if (method.classList.contains('active')) {
            const savedProduct = JSON.parse(localStorage.getItem('savedProduct'));
            const price = savedProduct.selling_price;
            selectedPaymentUrl = method.getAttribute('data-payment-url');
            selectedPaymentUrl = selectedPaymentUrl.replace(/am=[^&]+/, `am=${price}`);
            console.log(selectedPaymentUrl)
        }
    });

    if (!selectedPaymentUrl) {
        //alert('Please select a payment method');
        return;
    }

    window.open(selectedPaymentUrl, '');
}

const paymentMethods = document.querySelectorAll('.available-method');

paymentMethods.forEach(method => {
    method.addEventListener('click', function() {
        paymentMethods.forEach(m => m.classList.remove('active'));
        method.classList.add('active');
    });
});

// $(document).ready(function() {
//     // Function to handle payment method selection
//     $('.available-method').click(function() {
//         const selectedPaymentMethod = $(this).attr('pay-type');  // Get selected payment type

//         // Display loader
//         $('#loader').show();

//         // Simulate 3-second delay
//         setTimeout(function() {
//             // Hide loader after 3 seconds
//             $('#loader').hide();

//             // Redirect to thank you page
//             window.location.href = "thanks.html?payment_method=" + selectedPaymentMethod;
//         }, 3000);
//     });
// });