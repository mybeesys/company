import { useEffect, useState } from "react";
import { getRowName } from "../lang/Utils";
import { useSearchParams } from "react-router-dom";

const MenuSimple = ({ translations, dir }) => {
    const rootElement = document.getElementById("root");
    const info = JSON.parse(rootElement.getAttribute("info"));
    const blankurl = rootElement.getAttribute("blank-url");
    const urlList = JSON.parse(rootElement.getAttribute("list-url"));
    const [currentTab, setCurrentTab] = useState("all");
    const [nodes, setNodes] = useState([]);
    const [allProducts, setAllProducts] = useState([]);
    const [filteredProducts, setFilteredProducts] = useState([]);

    const urlParams = new URLSearchParams(window.location.search);
    const selectedProductsParam = urlParams.get("products");

    useEffect(() => {
        refreshMenu();
    }, []);

    const refreshMenu = () => {
        try {
            axios.get(urlList).then((response) => {
                let result = response.data;
                let allProducts1 = [];

                result.forEach((category) => {
                    allProducts1 = allProducts1.concat(
                        category.children_with_products
                    );
                });

                setNodes(result);
                setAllProducts(allProducts1);

                if (selectedProductsParam) {
                    const selectedProductIds = selectedProductsParam.split(",");
                    const filtered = allProducts1
                        .map((subCategory) => {
                            return {
                                ...subCategory,
                                products_for_sale:
                                    subCategory.products_for_sale.filter(
                                        (product) =>
                                            selectedProductIds.includes(
                                                product.id.toString()
                                            )
                                    ),
                            };
                        })
                        .filter(
                            (subCategory) =>
                                subCategory.products_for_sale.length > 0
                        );

                    setFilteredProducts(filtered);
                } else {
                    setFilteredProducts(allProducts1);
                }
            });
        } catch (error) {
            console.error("There was an error getting the products!", error);
        }
    };

    const displayProducts = selectedProductsParam
        ? filteredProducts
        : allProducts;

    return (
        <div class="container my-4">
            <div class="fs-1 fw-bolder">
                {`${getRowName(info.establishment)} ${
                    !!info.title ? "- " + info.title : ""
                } ${!!info.sub_title ? "- " + info.sub_title : ""}`}
            </div>

            {!selectedProductsParam && (
                <div class="card-toolbar row pt-5">
                    <div class="col-10">
                        <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-4 border-0 fw-bold custom-tabs">
                            <li class="nav-item" role="presentation">
                                <a
                                    class="nav-link active"
                                    id={`all_tab`}
                                    onClick={(e) => setCurrentTab(0)}
                                    data-bs-toggle="tab"
                                    role="tab"
                                    href={`#cat-all`}
                                >
                                    {translations.showAll}
                                </a>
                            </li>
                            {nodes.map((category, index) => (
                                <li class="nav-item" role="presentation">
                                    <a
                                        class="nav-link"
                                        id={`${category.id}_tab`}
                                        onClick={(e) =>
                                            setCurrentTab(index + 1)
                                        }
                                        data-bs-toggle="tab"
                                        role="tab"
                                        href={`#cat-${category.id}`}
                                    >
                                        {getRowName(category, dir)}
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </div>
                    <div
                        class="col-2"
                        style={{ "justify-content": "end", display: "flex" }}
                    >
                        {/* Empty div for alignment */}
                    </div>
                </div>
            )}

            <div class="tab-content">
                {!selectedProductsParam ? (
                    <>
                        <div
                            class="row mt-4 tab-pane fade show active"
                            id={`cat-all`}
                            role="tabpanel"
                            aria-labelledby={`all_tab`}
                        >
                            {displayProducts.map(
                                (subCategory) =>
                                    subCategory.products_for_sale.length >
                                        0 && (
                                        <div>
                                            <div class="container pb-3">
                                                <span class="title">{`${getRowName(
                                                    subCategory,
                                                    dir
                                                )} (${
                                                    subCategory
                                                        .products_for_sale
                                                        .length
                                                })`}</span>
                                            </div>
                                            <div class="container my-5">
                                                <div class="d-flex justify-content-between flex-wrap gap-4">
                                                    {subCategory.products_for_sale.map(
                                                        (product) => (
                                                            <ProductCard
                                                                product={
                                                                    product
                                                                }
                                                                dir={dir}
                                                                translations={
                                                                    translations
                                                                }
                                                                blankurl={
                                                                    blankurl
                                                                }
                                                            />
                                                        )
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    )
                            )}
                        </div>

                        {nodes.map((category) => (
                            <div
                                class="row mt-4 tab-pane fade"
                                id={`cat-${category.id}`}
                                role="tabpanel"
                                aria-labelledby={`${category.id}_tab`}
                            >
                                {category.children_with_products.map(
                                    (subCategory) =>
                                        subCategory.products_for_sale.length >
                                            0 && (
                                            <div>
                                                <div class="container pb-3">
                                                    <span class="title">{`${getRowName(
                                                        subCategory,
                                                        dir
                                                    )} (${
                                                        subCategory
                                                            .products_for_sale
                                                            .length
                                                    })`}</span>
                                                </div>
                                                <div class="container my-5">
                                                    <div class="d-flex justify-content-between flex-wrap gap-4">
                                                        {subCategory.products_for_sale.map(
                                                            (product) => (
                                                                <ProductCard
                                                                    product={
                                                                        product
                                                                    }
                                                                    dir={dir}
                                                                    translations={
                                                                        translations
                                                                    }
                                                                    blankurl={
                                                                        blankurl
                                                                    }
                                                                />
                                                            )
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        )
                                )}
                            </div>
                        ))}
                    </>
                ) : (
                    // Display only filtered products without tabs when products are selected
                    <div class="row mt-4">
                        {displayProducts.map(
                            (subCategory) =>
                                subCategory.products_for_sale.length > 0 && (
                                    <div>
                                        <div class="container pb-3">
                                            <span class="title">{`${getRowName(
                                                subCategory,
                                                dir
                                            )} (${
                                                subCategory.products_for_sale
                                                    .length
                                            })`}</span>
                                        </div>
                                        <div class="container my-5">
                                            <div class="d-flex justify-content-between flex-wrap gap-4">
                                                {subCategory.products_for_sale.map(
                                                    (product) => (
                                                        <ProductCard
                                                            product={product}
                                                            dir={dir}
                                                            translations={
                                                                translations
                                                            }
                                                            blankurl={blankurl}
                                                        />
                                                    )
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                )
                        )}
                    </div>
                )}
            </div>
        </div>
    );
};

const ProductCard = ({ product, dir, translations, blankurl }) => {
    return (
        <div
            class="card p-3"
            style={{ "max-width": "30rem", "min-width": "30rem" }}
        >
            <div class="row g-0">
                <div class="col-md-4">
                    <img
                        src={!!!product.image ? blankurl : `/${product.image}`}
                        class="img-fluid rounded-start"
                        alt={getRowName(product, dir)}
                        style={{ "object-fit": "cover" }}
                    />
                </div>
                <div class="col-md-8">
                    <div class="card-body">
                        <h5 class="card-title d-flex align-items-center">
                            <span class="me-2 text-success">🟢</span>{" "}
                            {getRowName(product, dir)}
                        </h5>
                        <p
                            style={{ "min-height": "2.6rem" }}
                            class="card-text text-muted mb-1"
                        >
                            {dir === "rtl"
                                ? product.description_ar
                                : product.description_en}
                        </p>
                        <p class="text-muted mb-1">
                            {`${translations.preparationTime1}: ${
                                product.preparation_time ?? ""
                            } ${translations.minutes}`}
                        </p>
                        <p class="text-muted mb-1">
                            {`${translations.calories}: ${
                                product.calories ?? ""
                            }`}
                        </p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <h5 class="text-primary mb-0">
                                {`${product.price_with_tax} $`}
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default MenuSimple;
