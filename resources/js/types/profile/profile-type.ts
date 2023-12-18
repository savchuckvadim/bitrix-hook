

export type Profile = {
    name: string
    secondName: string
    email: string
    role: Role
}
export enum Role {
    ADMIN = 'admin',
    CLIENT = 'client',
    MANAGER = 'manager'
}