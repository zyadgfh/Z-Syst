type Role = 'ADMIN' | 'PHARMACIST' | 'MANAGER' | 'STAFF';
export declare class RegisterDto {
    email: string;
    password: string;
    role?: Role;
}
export {};
