const Ziggy = {
    url: 'http:/\/localhost:8000',
    port: 8000,
    defaults: {},
    routes: {
        login: { uri: 'login', methods: ['GET', 'HEAD'] },
        'login.store': { uri: 'login', methods: ['POST'] },
        logout: { uri: 'logout', methods: ['POST'] },
        'password.request': {
            uri: 'forgot-password',
            methods: ['GET', 'HEAD'],
        },
        'password.reset': {
            uri: 'reset-password/{token}',
            methods: ['GET', 'HEAD'],
            parameters: ['token'],
        },
        'password.email': { uri: 'forgot-password', methods: ['POST'] },
        'password.update': { uri: 'reset-password', methods: ['POST'] },
        register: { uri: 'register', methods: ['GET', 'HEAD'] },
        'register.store': { uri: 'register', methods: ['POST'] },
        'verification.notice': {
            uri: 'email/verify',
            methods: ['GET', 'HEAD'],
        },
        'verification.verify': {
            uri: 'email/verify/{id}/{hash}',
            methods: ['GET', 'HEAD'],
            parameters: ['id', 'hash'],
        },
        'verification.send': {
            uri: 'email/verification-notification',
            methods: ['POST'],
        },
        'password.confirm': {
            uri: 'user/confirm-password',
            methods: ['GET', 'HEAD'],
        },
        'password.confirmation': {
            uri: 'user/confirmed-password-status',
            methods: ['GET', 'HEAD'],
        },
        'password.confirm.store': {
            uri: 'user/confirm-password',
            methods: ['POST'],
        },
        'two-factor.login': {
            uri: 'two-factor-challenge',
            methods: ['GET', 'HEAD'],
        },
        'two-factor.login.store': {
            uri: 'two-factor-challenge',
            methods: ['POST'],
        },
        'two-factor.enable': {
            uri: 'user/two-factor-authentication',
            methods: ['POST'],
        },
        'two-factor.confirm': {
            uri: 'user/confirmed-two-factor-authentication',
            methods: ['POST'],
        },
        'two-factor.disable': {
            uri: 'user/two-factor-authentication',
            methods: ['DELETE'],
        },
        'two-factor.qr-code': {
            uri: 'user/two-factor-qr-code',
            methods: ['GET', 'HEAD'],
        },
        'two-factor.secret-key': {
            uri: 'user/two-factor-secret-key',
            methods: ['GET', 'HEAD'],
        },
        'two-factor.recovery-codes': {
            uri: 'user/two-factor-recovery-codes',
            methods: ['GET', 'HEAD'],
        },
        'two-factor.regenerate-recovery-codes': {
            uri: 'user/two-factor-recovery-codes',
            methods: ['POST'],
        },
        home: { uri: '/', methods: ['GET', 'HEAD'] },
        dashboard: { uri: 'dashboard', methods: ['GET', 'HEAD'] },
        'members.index': { uri: 'members', methods: ['GET', 'HEAD'] },
        'members.create': { uri: 'members/create', methods: ['GET', 'HEAD'] },
        'members.store': { uri: 'members', methods: ['POST'] },
        'members.show': {
            uri: 'members/{member}',
            methods: ['GET', 'HEAD'],
            parameters: ['member'],
            bindings: { member: 'id' },
        },
        'members.edit': {
            uri: 'members/{member}/edit',
            methods: ['GET', 'HEAD'],
            parameters: ['member'],
            bindings: { member: 'id' },
        },
        'members.update': {
            uri: 'members/{member}',
            methods: ['PUT', 'PATCH'],
            parameters: ['member'],
            bindings: { member: 'id' },
        },
        'members.destroy': {
            uri: 'members/{member}',
            methods: ['DELETE'],
            parameters: ['member'],
            bindings: { member: 'id' },
        },
        'members.reactivate': {
            uri: 'members/{member}/reactivate',
            methods: ['PATCH'],
            parameters: ['member'],
            bindings: { member: 'id' },
        },
        'membership-applications.index': {
            uri: 'membership-applications',
            methods: ['GET', 'HEAD'],
        },
        'membership-applications.create': {
            uri: 'membership-applications/create',
            methods: ['GET', 'HEAD'],
        },
        'membership-applications.store': {
            uri: 'membership-applications',
            methods: ['POST'],
        },
        'membership-applications.show': {
            uri: 'membership-applications/{membership_application}',
            methods: ['GET', 'HEAD'],
            parameters: ['membership_application'],
        },
        'membership-applications.edit': {
            uri: 'membership-applications/{membership_application}/edit',
            methods: ['GET', 'HEAD'],
            parameters: ['membership_application'],
        },
        'membership-applications.update': {
            uri: 'membership-applications/{membership_application}',
            methods: ['PUT', 'PATCH'],
            parameters: ['membership_application'],
        },
        'membership-applications.destroy': {
            uri: 'membership-applications/{membership_application}',
            methods: ['DELETE'],
            parameters: ['membership_application'],
        },
        'membership-applications.submit': {
            uri: 'membership-applications/{membershipApplication}/submit',
            methods: ['PATCH'],
            parameters: ['membershipApplication'],
            bindings: { membershipApplication: 'id' },
        },
        'membership-applications.review': {
            uri: 'membership-applications/{membershipApplication}/review',
            methods: ['PATCH'],
            parameters: ['membershipApplication'],
            bindings: { membershipApplication: 'id' },
        },
        'membership-applications.approve': {
            uri: 'membership-applications/{membershipApplication}/approve',
            methods: ['PATCH'],
            parameters: ['membershipApplication'],
            bindings: { membershipApplication: 'id' },
        },
        'share-capital.create': {
            uri: 'members/{member}/share-capital/create',
            methods: ['GET', 'HEAD'],
            parameters: ['member'],
            bindings: { member: 'id' },
        },
        'share-capital.store': {
            uri: 'members/{member}/share-capital',
            methods: ['POST'],
            parameters: ['member'],
            bindings: { member: 'id' },
        },
        'cbu.create': {
            uri: 'members/{member}/cbu/create',
            methods: ['GET', 'HEAD'],
            parameters: ['member'],
            bindings: { member: 'id' },
        },
        'cbu.store': {
            uri: 'members/{member}/cbu',
            methods: ['POST'],
            parameters: ['member'],
            bindings: { member: 'id' },
        },
        'savings.deposit.create': {
            uri: 'members/{member}/savings/deposit',
            methods: ['GET', 'HEAD'],
            parameters: ['member'],
            bindings: { member: 'id' },
        },
        'savings.deposit': {
            uri: 'members/{member}/savings/deposit',
            methods: ['POST'],
            parameters: ['member'],
            bindings: { member: 'id' },
        },
        'savings.withdrawal.create': {
            uri: 'members/{member}/savings/withdrawal',
            methods: ['GET', 'HEAD'],
            parameters: ['member'],
            bindings: { member: 'id' },
        },
        'savings.withdrawal': {
            uri: 'members/{member}/savings/withdrawal',
            methods: ['POST'],
            parameters: ['member'],
            bindings: { member: 'id' },
        },
        'loans.create': { uri: 'loans/create', methods: ['GET', 'HEAD'] },
        'loans.store': { uri: 'loans', methods: ['POST'] },
        'loans.show': {
            uri: 'loans/{loan}',
            methods: ['GET', 'HEAD'],
            parameters: ['loan'],
            bindings: { loan: 'id' },
        },
        'profile.edit': { uri: 'settings/profile', methods: ['GET', 'HEAD'] },
        'profile.update': { uri: 'settings/profile', methods: ['PATCH'] },
        'profile.destroy': { uri: 'settings/profile', methods: ['DELETE'] },
        'security.edit': {
            uri: 'settings/security',
            methods: ['GET', 'HEAD'],
        },
        'user-password.update': { uri: 'settings/password', methods: ['PUT'] },
        'appearance.edit': {
            uri: 'settings/appearance',
            methods: ['GET', 'HEAD'],
        },
        'storage.local': {
            uri: 'storage/{path}',
            methods: ['GET', 'HEAD'],
            wheres: { path: '.*' },
            parameters: ['path'],
        },
        'storage.local.upload': {
            uri: 'storage/{path}',
            methods: ['PUT'],
            wheres: { path: '.*' },
            parameters: ['path'],
        },
    },
};
if (typeof window !== 'undefined' && typeof window.Ziggy !== 'undefined') {
    Object.assign(Ziggy.routes, window.Ziggy.routes);
}
export { Ziggy };
