// ----------------------------------------------------------------------

function path(root, sublink) {
  return `${root}${sublink}`;
}

const ROOTS_AUTH = '/auth';
const ROOTS_DASHBOARD = '/dashboard';
const ROOTS_CUSTOMER = '/customer';
const ROOTS_ADMIN = '/secure';
// ----------------------------------------------------------------------

export const PATH_AUTH = {
  root: ROOTS_AUTH,
  login: path(ROOTS_AUTH, '/login'),
  register: path(ROOTS_AUTH, '/register'),
  resetPassword: path(ROOTS_AUTH, '/reset-password'),
  verify: path(ROOTS_AUTH, '/verify'),
  termsOfService: path(ROOTS_AUTH, '/terms-of-service'),
};

export const PATH_PAGE = {
  comingSoon: '/coming-soon',
  maintenance: '/maintenance',
  pricing: '/pricing',
  payment: '/payment',
  about: '/about-us',
  contact: '/contact-us',
  faqs: '/faqs',
  page404: '/404',
  page500: '/500',
  components: '/components'
};
export const PATH_ADMIN = {
  root: ROOTS_ADMIN,
  general: {
    app: path(ROOTS_ADMIN, '/app'),
    notif: path(ROOTS_ADMIN, '/notif'),
    info: path(ROOTS_ADMIN, '/info'),
    message: path(ROOTS_ADMIN, '/welcome'),
    feature: path(ROOTS_ADMIN, '/feature'),
    newfeature: path(ROOTS_ADMIN, '/newfeature'),
    app_download: path(ROOTS_ADMIN, '/appDownlod'),
    newapp_download: path(ROOTS_ADMIN, '/newappDownload'),
    payment_key: path(ROOTS_ADMIN, '/paymentKey'),
    calculator: path(ROOTS_ADMIN, '/calculator'),
    charity: path(ROOTS_ADMIN, '/charity'),
    support: path(ROOTS_ADMIN, '/support'),
  },
  user: {
    root: path(ROOTS_ADMIN, '/users'),
    all_user: path(ROOTS_ADMIN, '/users/users-all'),
    newUser: path(ROOTS_ADMIN, '/users/newuser'),
    creditUser: path(ROOTS_ADMIN, '/users/UserCredit'),
    upgradeuser: path(ROOTS_ADMIN, '/users/upgrade'),
    resetpassword: path(ROOTS_ADMIN, '/users/resetpassword'),
    automedaccount: path(ROOTS_ADMIN, '/users/autoAccount'),
    userbank: path(ROOTS_ADMIN, '/users/userbank'),
    bannednumber: path(ROOTS_ADMIN, '/users/banned'),
    addbanned: path(ROOTS_ADMIN, '/users/addbanned'),
    stockuser: path(ROOTS_ADMIN, '/users/stock'),
    userskyc: path(ROOTS_ADMIN, '/userskyc')
  },
  companies: {
    root: path(ROOTS_ADMIN, '/companies'),
    list: path(ROOTS_ADMIN, '/companies'),
    pendingKyc: path(ROOTS_ADMIN, '/companies/pending-kyc'),
    detail: (id) => path(ROOTS_ADMIN, `/companies/${id}`)
  },
  customers: {
    root: path(ROOTS_ADMIN, '/customers'),
    list: path(ROOTS_ADMIN, '/customers'),
    detail: (id) => path(ROOTS_ADMIN, `/customers/${id}`)
  },
  sendmessage: {
    root: path(ROOTS_ADMIN, '/sendmessage'),
    gmail: path(ROOTS_ADMIN, '/sendmessage/gmail'),
    system: path(ROOTS_ADMIN, '/sendmessage/system'),
    bulksms: path(ROOTS_ADMIN, '/sendmessage/bulksms')
  },
  trans: {
    root: path(ROOTS_ADMIN, '/trans'),
    history: path(ROOTS_ADMIN, '/trans/history'),
    data: path(ROOTS_ADMIN, '/trans/data'),
    airtime: path(ROOTS_ADMIN, '/trans/airtime'),
    cable: path(ROOTS_ADMIN, '/trans/cable'),
    bill: path(ROOTS_ADMIN, '/trans/bill'),
    result: path(ROOTS_ADMIN, '/trans/result'),
    bulksms: path(ROOTS_ADMIN, '/trans/bulksms'),
    cash: path(ROOTS_ADMIN, '/trans/cash'),
    deposit: path(ROOTS_ADMIN, '/trans/deposit'),
    stock: path(ROOTS_ADMIN, '/trans/stock'),
    manual: path(ROOTS_ADMIN, '/trans/manual'),
    data_card: path(ROOTS_ADMIN, '/trans/data_card'),
    recharge_card: path(ROOTS_ADMIN, '/trans/recharge_card'),
    transfer: path(ROOTS_ADMIN, '/trans/transfer'),
    cards: path(ROOTS_ADMIN, '/trans/cards'),
    statement: path(ROOTS_ADMIN, '/trans/statement'),
    report: path(ROOTS_ADMIN, '/trans/report'),
  },
  discount: {
    root: path(ROOTS_ADMIN, '/discount'),
    airtime: path(ROOTS_ADMIN, '/discount/airtime'),
    cash: path(ROOTS_ADMIN, '/discount/cash'),
    bill: path(ROOTS_ADMIN, '/discount/bill'),
    exam: path(ROOTS_ADMIN, '/discount/exam'),
    cable: path(ROOTS_ADMIN, '/discount/cable'),
    other: path(ROOTS_ADMIN, '/discount/other'),
    banks: path(ROOTS_ADMIN, '/discount/banks'),
  },
  lock: {
    root: path(ROOTS_ADMIN, '/lock'),
    airtime: path(ROOTS_ADMIN, '/lock/airtime'),
    data: path(ROOTS_ADMIN, '/lock/data'),
    exam: path(ROOTS_ADMIN, '/lock/result'),
    cable: path(ROOTS_ADMIN, '/lock/cable'),
    other: path(ROOTS_ADMIN, '/lock/other'),
    banks: path(ROOTS_ADMIN, '/lock/banks-global'),
    bank_list: path(ROOTS_ADMIN, '/lock/banks'),
    virtualaccounts: path(ROOTS_ADMIN, '/lock/virtualaccounts'),
    data_card: path(ROOTS_ADMIN, '/lock/data_card'),
    recharge_card: path(ROOTS_ADMIN, '/lock/recharge_card')
  },
  plan: {
    root: path(ROOTS_ADMIN, '/plan'),
    data: path(ROOTS_ADMIN, '/plan/data'),
    bill: path(ROOTS_ADMIN, '/plan/bill'),
    exam: path(ROOTS_ADMIN, '/plan/exam'),
    cards: path(ROOTS_ADMIN, '/plan/cards'),
    cable: path(ROOTS_ADMIN, '/plan/cable'),
    network: path(ROOTS_ADMIN, '/plan/network'),
    newdata: path(ROOTS_ADMIN, '/plan/newdata'),
    newcable: path(ROOTS_ADMIN, '/plan/newcable'),
    newbill: path(ROOTS_ADMIN, '/plan/newbill'),
    newresult: path(ROOTS_ADMIN, '/plan/newresult'),
    data_card_plan: path(ROOTS_ADMIN, '/plan/data_card_plan'),
    store_data_card: path(ROOTS_ADMIN, '/plan/store_data_card'),
    recharge_card_plan: path(ROOTS_ADMIN, '/plan/recharge_card_plan'),
    store_recharge_card: path(ROOTS_ADMIN, '/plan/store_recharge_card'),
    new_data_card_plan: path(ROOTS_ADMIN, '/plan/new_data_card_plan'),
    new_recharge_card_plan: path(ROOTS_ADMIN, '/plan/new_recharge_card_plan'),
    add_store_data_card: path(ROOTS_ADMIN, '/plan/add_store_data_card'),
    add_store_recharge_card: path(ROOTS_ADMIN, '/plan/add_store_recharge_card')
  },
  api: {
    root: path(ROOTS_ADMIN, '/api'),
    Habukhan: path(ROOTS_ADMIN, '/api/system'),
    adex: path(ROOTS_ADMIN, '/api/adex'),
    virus: path(ROOTS_ADMIN, '/api/virus'),
    msorg: path(ROOTS_ADMIN, '/api/msorg'),
    other: path(ROOTS_ADMIN, '/api/other'),
    web: path(ROOTS_ADMIN, '/api/web'),
    requests: path(ROOTS_ADMIN, '/api/requests'), // ADDED for sidebar
  },
  webhooks: {
    root: path(ROOTS_ADMIN, '/webhooks'), // ADDED for sidebar
  },
  selection: {
    root: path(ROOTS_ADMIN, '/selection'),
    data: path(ROOTS_ADMIN, '/selection/data'),
    airtime: path(ROOTS_ADMIN, '/selection/airtime'),
    cash: path(ROOTS_ADMIN, '/selection/cash'),
    cable: path(ROOTS_ADMIN, '/selection/cable'),
    bill: path(ROOTS_ADMIN, '/selection/bill'),
    bulksms: path(ROOTS_ADMIN, '/selection/bulksms'),
    exam: path(ROOTS_ADMIN, '/selection/exam'),
    data_card: path(ROOTS_ADMIN, '/selection/data_card'),
    recharge_card: path(ROOTS_ADMIN, '/selection/recharge_card'),
    virtualaccounts: path(ROOTS_ADMIN, '/selection/virtualaccounts'),
    transfer_settings: path(ROOTS_ADMIN, '/selection/bank-transfer')
  }
}
export const PATH_DASHBOARD = {
  root: ROOTS_DASHBOARD,
  general: {
    app: path(ROOTS_DASHBOARD, '/app'),
    exam: path(ROOTS_DASHBOARD, '/exam'),
    buydata: path(ROOTS_DASHBOARD, '/buydata'),
    buyairtime: path(ROOTS_DASHBOARD, '/buyairtime'),
    buycable: path(ROOTS_DASHBOARD, '/buycable'),
    buybill: path(ROOTS_DASHBOARD, '/buybill'),
    cash: path(ROOTS_DASHBOARD, '/cash'),
    earning: path(ROOTS_DASHBOARD, '/earning'),
    bulksms: path(ROOTS_DASHBOARD, '/bulksms'),
    device: path(ROOTS_DASHBOARD, '/device'),
    notif: path(ROOTS_DASHBOARD, '/notif'),
    invioce: path(ROOTS_DASHBOARD, '/invoice'),
    stock: path(ROOTS_DASHBOARD, '/stock'),
    calculator: path(ROOTS_DASHBOARD, '/calculator'),
    pricing: path(ROOTS_DASHBOARD, '/pricing'),
    data_card: path(ROOTS_DASHBOARD, '/data_card'),
    recharge_card: path(ROOTS_DASHBOARD, '/recharge_card'),
    transactions: path(ROOTS_DASHBOARD, '/transactions'),
    ra_transactions: path(ROOTS_DASHBOARD, '/ra-transactions'),
    wallet: path(ROOTS_DASHBOARD, '/wallet'),
    reserved_account: path(ROOTS_DASHBOARD, '/reserved-account'),
    customers: path(ROOTS_DASHBOARD, '/customers'),
    customer_view: path(ROOTS_DASHBOARD, '/customers/view'),
    cards: path(ROOTS_DASHBOARD, '/cards'),
    transfer: path(ROOTS_DASHBOARD, '/withdraw'),
    faqs: path(ROOTS_DASHBOARD, '/faqs'),
    settings: path(ROOTS_DASHBOARD, '/settings'),
    webhook: path(ROOTS_DASHBOARD, '/webhook'),
    webhook_logs: path(ROOTS_DASHBOARD, '/webhook-logs'),
    api_logs: path(ROOTS_DASHBOARD, '/api-logs'),
    audit_logs: path(ROOTS_DASHBOARD, '/audit-logs'),
    api_docs: path(ROOTS_DASHBOARD, '/api-documentation'),
    developer: path(ROOTS_DASHBOARD, '/developer'),
    developers: path(ROOTS_DASHBOARD, '/developers'),
    kyc: path(ROOTS_DASHBOARD, '/kyc'),
    changepin: path(ROOTS_DASHBOARD, '/change-pin'),
    support: path(ROOTS_DASHBOARD, '/support'),
    activate: path(ROOTS_DASHBOARD, '/activate/business'),
    new_customer: path(ROOTS_DASHBOARD, '/customers/new'),
    new_reserved_account: path(ROOTS_DASHBOARD, '/reserved-account/create'),
  },
  fund: {
    root: path(ROOTS_DASHBOARD, '/fund'),
    account: path(ROOTS_DASHBOARD, '/fund/account'),
    atm: path(ROOTS_DASHBOARD, '/fund/atm'),
    manual: path(ROOTS_DASHBOARD, '/fund/manual'),
    paystack: path(ROOTS_DASHBOARD, '/fund/paystack'),
    link_bvn: path(ROOTS_DASHBOARD, '/fund/update-kyc'),
    link_dynamic_account: path(ROOTS_DASHBOARD, '/fund/dynamic-account'),
  },
  trans: {
    root: path(ROOTS_DASHBOARD, '/trans'),
    history: path(ROOTS_DASHBOARD, '/trans/history'),
    data: path(ROOTS_DASHBOARD, '/trans/data'),
    airtime: path(ROOTS_DASHBOARD, '/trans/airtime'),
    cable: path(ROOTS_DASHBOARD, '/trans/cable'),
    bill: path(ROOTS_DASHBOARD, '/trans/bill'),
    result: path(ROOTS_DASHBOARD, '/trans/result'),
    bulksms: path(ROOTS_DASHBOARD, '/trans/bulksms'),
    cash: path(ROOTS_DASHBOARD, '/trans/cash'),
    deposit: path(ROOTS_DASHBOARD, '/trans/deposit'),
    stock: path(ROOTS_DASHBOARD, '/trans/stock'),
    airtimecash: path(ROOTS_DASHBOARD, '/trans/airtimecash'),
    manualfunding: path(ROOTS_DASHBOARD, '/trans/manualfunding'),
    data_card: path(ROOTS_DASHBOARD, '/trans/data_card'),
    recharge_card: path(ROOTS_DASHBOARD, '/trans/recharge_card')
  },
  customer: {
    root: path(ROOTS_CUSTOMER, '/customer'),
    history: path(ROOTS_CUSTOMER, '/customer/history'),
    data: path(ROOTS_CUSTOMER, '/customer/data'),
    airtime: path(ROOTS_CUSTOMER, '/customer/airtime'),
    cable: path(ROOTS_CUSTOMER, '/customer/cable'),
    bill: path(ROOTS_CUSTOMER, '/customer/bill'),
    result: path(ROOTS_CUSTOMER, '/customer/result'),
    bulksms: path(ROOTS_CUSTOMER, '/customer/bulksms'),
    cash: path(ROOTS_CUSTOMER, '/customer/cash'),
    deposit: path(ROOTS_CUSTOMER, '/customer/deposit'),
    stock: path(ROOTS_CUSTOMER, '/customer/stock'),
    airtimecash: path(ROOTS_CUSTOMER, '/customer/airtimecash'),
    manualfunding: path(ROOTS_CUSTOMER, '/customer/manualfunding'),
    data_card: path(ROOTS_CUSTOMER, '/customer/data_card'),
    recharge_card: path(ROOTS_CUSTOMER, '/customer/recharge_card')
  },
  credituser: path(ROOTS_CUSTOMER, '/credit'),

  user: {
    root: path(ROOTS_DASHBOARD, '/user'),
    account: path(ROOTS_DASHBOARD, '/user/account')
  },
};


export const PATH_DOCS = "/documentation";
export const DOCS = {
  root: path(PATH_DOCS, '/home'),
  buydata: path(PATH_DOCS, '/data'),
  buyairtime: path(PATH_DOCS, '/airtime'),
  airtime_to_cash: path(PATH_DOCS, '/airtime_to_cash'),
  buycable: path(PATH_DOCS, '/cable'),
  buybill: path(PATH_DOCS, '/bill'),
  buyexam: path(PATH_DOCS, '/exam'),
  bulksms: path(PATH_DOCS, '/bulksms'),
  verifyiuc: path(PATH_DOCS, '/verifyiuc'),
  verifymetter: path(PATH_DOCS, '/meter'),
  webhook: path(PATH_DOCS, '/webhook'),
  data_card: path(PATH_DOCS, '/data_card'),
  recharge_card: path(PATH_DOCS, '/recharge_card')
}

