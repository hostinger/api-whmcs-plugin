# Hostinger WHMCS Module

Integrate Hostinger VPS/Cloud services directly into your WHMCS billing system. Automate provisioning, management, and billing with seamless API connectivity. 🚀

## ✨ Features 

- **Automated VPS provisioning** - Purchase new VPS instances
- **Real-time management** - Restart, reinstall, password changes
- **Usage tracking** - Disk space and bandwidth monitoring
- **Backup & snapshots** - Create, restore, and manage backups
- **DNS management** - Set nameservers and PTR records
- **Client area integration** - Full VPS control panel for end users

## 📋 Requirements 

- WHMCS 8.0 or higher
- PHP 8.1+
- Hostinger account and API token

## 🚀 Getting Started

### 1. Get your Hostinger API token 🔑

1. Login to [hPanel](https://hpanel.hostinger.com)
2. Navigate to `Account Information` section or [click here]( https://hpanel.hostinger.com/profile/api)
3. Click on `API` menu item
4. Click `New token` button
5. Fill in information and click `Generate` button

*Tip: Store the token securely. You’ll paste it in WHMCS as the server Password.*

**Security Note**: Use dedicated API tokens for WHMCS integration. Rotate tokens regularly and revoke unused ones. 🔒

### 2. Installation 📦

1. Download the latest release: 
   https://github.com/hostinger/api-whmcs-plugin/releases
2. Upload archive folder contents to your WHMCS installation root directory.
3. Login to WHMCS admin panel.
4. Navigate to `Apps & Integrations`
5. Click `Browse` button and select `VPS/Cloud` or enter `Hostinger` in search field.
6. Click `Create New Server`

### 3. Server Configuration ⚙️

Fill in the following fields:
 - Module: `Hostinger`
 - Hostname: `developers.hostinger.com`
 - Username: `<leave empty>`
 - Password `<your API token>`
 - Access Hash: `<leave empty>`
 - 
Click `Test Connection` to verify setup, then `Save Changes`.

### 4. Product Setup 📋

1. Create a new product in WHMCS
2. Set product type to `Server / VPS`
3. Choose `Hostinger` as the module
4. Configure the module settings:
   - **Plan**: Select from available VPS plans
   - **Datacenter**: Choose default location (optional)
   - **OS Template**: Set default operating system (optional)


Now you can add Hostinger VPS products. Refer to [WHMCS documentation](https://docs.whmcs.com/8-13/products/product-tutorials/create-a-product/) for more information.

## 📚 Support

- **API Documentation**: [Hostinger Developer Docs](https://developers.hostinger.com)
- **Report Issues**: [Create a GitHub Issue](https://github.com/hostinger/api-whmcs-plugin/issues/new)
- **Get Support**: [Browse existing issues](https://github.com/hostinger/api-whmcs-plugin/issues) or open a new one

##  🤝 Contributing

We welcome contributions! Please:
1. Fork the repository
2. Create a feature branch
3. Submit a pull request with clear description

## 📄 License

This module is provided under the MIT license. See [LICENSE](https://github.com/hostinger/api-whmcs-plugin/blob/main/LICENSE) file for details.

---


