/* Senast uppdaterad 2026-10-12 10:00 (Asia/Bangkok) */
const { chromium } = require('@playwright/test');
(async()=>{
  const base=process.env.COSMOS_TEST_BASE;
  if(!base)throw Error('COSMOS_TEST_BASE required');
  const browser=await chromium.launch({headless:true});
  try{
    const page=await browser.newPage({viewport:{width:390,height:844}});
    await page.goto(base+'login.php');
    await page.locator('#access-word').fill('huset');
    await page.locator('button[type=submit]').click();
    await page.goto(base+'en/book/');
    await page.locator('#cosmosBookingForm').waitFor();
    const dates=await page.evaluate(()=>{const now=new Date(),t=new Intl.DateTimeFormat('en-US',{timeZone:'Asia/Bangkok',year:'numeric',month:'2-digit',day:'2-digit'}).formatToParts(now);const p=Object.fromEntries(t.map(x=>[x.type,x.value]));const d=new Date(Date.UTC(Number(p.year),Number(p.month)-1,Number(p.day)+40));return [d.toISOString().slice(0,10),new Date(d.getTime()+2*86400000).toISOString().slice(0,10)]});
    await page.locator('#cosmosCheckin').fill(dates[0]);
    await page.locator('#cosmosCheckout').fill(dates[1]);
    await page.locator('#cosmosQuoteButton').click();
    await page.locator('#cosmosPrice').getByText('THB', {exact:false}).first().waitFor();
    await page.locator('[name=name]').fill('Browser Test');
    await page.locator('[name=email]').fill('browser@example.invalid');
    await page.locator('[name=privacy]').check();
    await page.locator('#cosmosBookingForm [type=submit]').click();
    await page.getByText('Your enquiry has been received', {exact:false}).waitFor();
    const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>window.innerWidth+2);
    if(overflow)throw Error('booking form overflows at 390px');
    await page.goto(base+'sv/book/');
    if(!await page.getByText('Incheckning').count())throw Error('Swedish booking form missing');
    if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+2))throw Error('Swedish form overflows');
    await page.locator('#cosmosCheckin').fill('2020-01-01');
    await page.locator('#cosmosCheckout').fill('2020-01-02');
    await page.locator('#cosmosQuoteButton').click();
    await page.getByText('Kontrollera uppgifterna du har angett.').waitFor();
    await page.goto(base+'th/book/');
    if(!await page.getByText('วันเข้าพัก').count())throw Error('Thai booking form missing');
    if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+2))throw Error('Thai form overflows');
    await page.locator('#cosmosCheckin').fill('2020-01-01');
    await page.locator('#cosmosCheckout').fill('2020-01-02');
    await page.locator('#cosmosQuoteButton').click();
    await page.getByText('โปรดตรวจสอบข้อมูลที่กรอก').waitFor();
    console.log('PASS open booking UI at 390px: quote and inquiry');
  }finally{await browser.close()}
})().catch(error=>{console.error(error);process.exitCode=1});
