import { ProductsService } from './products.service';
export declare class ProductsController {
    private readonly productsService;
    constructor(productsService: ProductsService);
    getProducts(branchId: number): Promise<{
        id: number;
        sku: string;
        name: string;
        description: string;
        price: number;
        category: string;
        stock: number;
    }[]>;
}
