Welcome to Evolution 3
===
## A flexible PHP SDK that is designed for developing and sharing code.

You can also count on E3 do these things reliably:

* Optimize your code.
* Keep your application secure.
* Shorten your developer onramping process.
* Have the best documentation in the world.
* Help you distribute your awesome code.
* Help you debug your code in seconds.
* And more...

# Installing and Creating your first site

Install git and clone this repository or download and extract it into a folder.

    myfolder/EvolutionSDK
    
Create a folder for your new site and a configure folder

    myfolder/MySite
    myfolder/MySite/configure
    
Add your domain to hosts file
    
Add domains.txt with your domain

    myfolder/MySite/configure/domains.txt

Contents:

    mysite.dev
    
Create an en environment yaml file:

    myfolder/MySite.environment.yaml
    
Contents:

    ---
        Airbrake.Enabled: no
        Development.Master: yes
        Session.Enabled: no
        SQL.Enabled: no

Then setup Apache with virtual hosts:

    NameVirtualHost *:80
    
    # EvolutionSDK
    <VirtualHost *:80>
      DocumentRoot "/path/to/myfolder/EvolutionSDK"
    	ServerName evolution.dev
      ServerAlias mysite.dev
    <Directory /path/to/myfolder/EvolutionSDK>
    	AllowOverride All
    </Directory>
    </VirtualHost>

# Getting Started
The EvolutionSDK is built around this simple concept:  
**"Make it easy to write code that anybody can implement into their development process without the slightest pain."**

> This SDK came out of years of development experience and much experimenting and research. Every web developer always intends to re-use their code in another application, but often times it's very difficult to do that without a lot of reworking and rebuilding of the original code. We felt that was unacceptable and started looking at our code to see what aspects were completely application agnostic. What we discovered was that over 90% of the application logic could be written completely independent of the end product. So we started building on that idea. Welcome to E3. - **David Boskovic, Lead Engineer**

To understand the layout of the framework. Let's take a look at an example application.

## Airline Ticket Booking Software

Let's break this application down into different groups of application logic.

### Your Old Todo List

1. Membership / Login / Registration Functionality
2. Payment Functionality
3. Email Communication Functionality
4. SMS Functionality
5. Database Access Functionality
6. Mobile Boarding Pass Functionality
7. Airplane Seat Selection Functionality
8. Flight Tracking Functionality
9. Seat Inventory / Pricing Algorithms
10. Receipt / Invoice Functionality

As you can see, your application will use all of these features, but... each of them could be shared with or sold to other developers, and most of them have already been created for you.

### Your New Todo List

1. Airplane Seat Selection Functionality
2. Flight Tracking Functionality
3. Seat Inventory / Pricing Algorithms

You see... all the other code you want is already written and in the developer community. In this case, let's say they're already deployed as E3 bundles. All you have to do is install a few logic bundles and you have an application full of functionality ready to begin developing on.

## How is this different from using other open source classes or functionality.

phpclasses.org is a great place to find pre-written code. But it, and almost all open source software has one big flaw. **It doesn't integrate well.** You might grab a registration / user management application that has some shady back end UI to it and is almost impossible to understand. You might find an open source SMS class on github that's awesome but doesn't connect with your user database automatically. You might find a Paypal class that helps you process payments but it doesn't automatically update your receipts when the payment processes.

When you start connecting all these different open source pieces your software immediately becomes fragmented and difficult to debug. You end up spending too much time frustrated and not enough time developing awesome products.

----------------------------------

#E3 &rarr; Where Code Is a Product

Developers, listen up. This is your chance to create an incredible product that is nothing but pure, beautiful, fully tested and documented functionality. E3 is here to help you do what you're best at, hack code!